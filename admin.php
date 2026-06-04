<?php
if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

global $template;

include_once(PHPWG_ROOT_PATH . 'admin/include/functions.php');

bsm_ensure_schema();

$selected_site_id = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;
$selected_root_category_id = isset($_GET['root_category_id']) ? (int) $_GET['root_category_id'] : 0;
$selected_metadata_mode = (isset($_GET['metadata_mode']) && $_GET['metadata_mode'] === 'all') ? 'all' : 'new_only';
$sites = bsm_get_local_sites();
if ($selected_site_id <= 0 && !empty($sites))
{
  $selected_site_id = (int) $sites[0]['id'];
}

if (isset($_GET['mode']) && $_GET['mode'] === 'api')
{
  ob_start();
  register_shutdown_function(
    function () {
      $error = error_get_last();
      if (!$error)
      {
        return;
      }

      if (!in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR)))
      {
        return;
      }

      while (ob_get_level() > 0)
      {
        ob_end_clean();
      }

      if (!headers_sent())
      {
        header('Content-Type: application/json; charset=utf-8');
      }

      echo json_encode(
        array(
          'success' => false,
          'message' => 'PHP fatal: ' . $error['message'] . ' in ' . basename($error['file']) . ':' . $error['line'],
        )
      );
    }
  );

  if (!empty($_POST))
  {
    check_pwg_token();
  }

  $response = array('success' => false);
  $api_action = isset($_POST['job_action']) ? $_POST['job_action'] : '';

  if ($api_action === 'start')
  {
    $site_id = bsm_get_post_int('site_id');
    $root_category_id = bsm_get_post_int('root_category_id');
    $metadata_mode = (isset($_POST['metadata_mode']) && $_POST['metadata_mode'] === 'all') ? 'all' : 'new_only';
    $job = bsm_start_job($site_id, $root_category_id, $metadata_mode);

    $response = array(
      'success' => $job !== null,
      'job' => bsm_prepare_job_for_output($job),
      'message' => $job ? '' : l10n('The bulk sync job could not be started.'),
    );
  }
  elseif ($api_action === 'next_step')
  {
    $job = bsm_get_active_job();
    $step_data = $job ? bsm_get_next_step_payload($job) : array(
      'job' => null,
      'payload' => array('done' => true),
    );

    $response = array(
      'success' => $job !== null,
      'job' => bsm_prepare_job_for_output($step_data['job']),
      'payload' => $step_data['payload'],
      'message' => $job ? '' : l10n('No active bulk sync job was found.'),
    );
  }
  elseif ($api_action === 'complete_step')
  {
    $job_id = bsm_get_post_int('job_id');
    $job = bsm_complete_step($job_id);
    $response = array(
      'success' => $job !== null,
      'job' => bsm_prepare_job_for_output($job),
      'message' => $job ? '' : l10n('The current batch could not be completed.'),
    );
  }
  elseif ($api_action === 'cancel')
  {
    bsm_cancel_active_jobs();
    $response = array('success' => true, 'message' => '');
  }

  $debug_output = trim(ob_get_clean());
  if ($debug_output !== '')
  {
    if (empty($response['message']))
    {
      $response['message'] = $debug_output;
    }
    else
    {
      $response['message'] .= "\n" . $debug_output;
    }
  }

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($response);
  exit();
}

$active_job = bsm_get_active_job();
if ($active_job)
{
  $selected_site_id = $active_job['site_id'];
  $selected_root_category_id = $active_job['root_category_id'];
  $selected_metadata_mode = $active_job['metadata_mode'];
}

$active_job = bsm_prepare_job_for_output($active_job);

$root_categories = $selected_site_id > 0 ? bsm_get_root_categories($selected_site_id) : array();

$template->assign(
  array(
    'BSM_ADMIN' => BSM_ADMIN,
    'BSM_TOKEN' => get_pwg_token(),
    'bsm_sites' => $sites,
    'bsm_selected_site_id' => $selected_site_id,
    'bsm_selected_root_category_id' => $selected_root_category_id,
    'bsm_selected_metadata_mode' => $selected_metadata_mode,
    'bsm_root_categories' => $root_categories,
    'bsm_active_job' => $active_job,
  )
);

$template->set_filenames(
  array(
    'plugin_admin_content' => dirname(__FILE__) . '/admin.tpl',
  )
);

$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
?>
