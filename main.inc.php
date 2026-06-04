<?php
/*
Version: 0.1.0
Plugin Name: Bulk Sync Manager
Plugin URI: https://github.com/frankhommes/piwigo_tam
Author: Frank Hommes
Author URI: http://www.freanki.net/
Description: Runs large Piwigo file synchronizations in short batches to avoid request timeouts.
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

global $prefixeTable;

defined('BSM_ID') or define('BSM_ID', basename(dirname(__FILE__)));
define('BSM_PATH', PHPWG_PLUGINS_PATH . BSM_ID . '/');
define('BSM_ADMIN', get_root_url() . 'admin.php?page=plugin-' . BSM_ID);
define('BSM_JOBS_TABLE', $prefixeTable . 'bulk_sync_manager_jobs');
define('BSM_SCAN_BATCH_SIZE', 25);
define('BSM_LOG_LIMIT', 12);

load_language('plugin.lang', BSM_PATH);

if (defined('IN_ADMIN'))
{
  add_event_handler('get_admin_plugin_menu_links', 'bsm_admin_menu_links');
  require_once(BSM_PATH . 'include/admin_events.inc.php');
  add_event_handler('init', 'bsm_admin_init');
}

function bsm_admin_init()
{
  bsm_ensure_schema();

  if (!defined('CURRENT_DATE'))
  {
    list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));
    define('CURRENT_DATE', $dbnow);
  }
}

function bsm_ensure_schema()
{
  $query = '
CREATE TABLE IF NOT EXISTS ' . BSM_JOBS_TABLE . ' (
  id int NOT NULL AUTO_INCREMENT,
  status varchar(20) NOT NULL DEFAULT "idle",
  phase varchar(20) NOT NULL DEFAULT "directory_scan",
  site_id int NOT NULL,
  root_category_id int DEFAULT NULL,
  total_categories int NOT NULL DEFAULT 0,
  processed_categories int NOT NULL DEFAULT 0,
  current_category_id int DEFAULT NULL,
  current_category_name varchar(255) DEFAULT NULL,
  current_message text DEFAULT NULL,
  metadata_mode varchar(20) NOT NULL DEFAULT "new_only",
  directory_queue longtext DEFAULT NULL,
  scanned_directories int NOT NULL DEFAULT 0,
  created_categories int NOT NULL DEFAULT 0,
  recent_log longtext DEFAULT NULL,
  created_on datetime NOT NULL,
  updated_on datetime NOT NULL,
  finished_on datetime DEFAULT NULL,
  PRIMARY KEY (id),
  KEY bsm_status (status),
  KEY bsm_site (site_id)
) DEFAULT CHARSET=utf8mb4
;';

  pwg_query($query);

  bsm_ensure_job_column('directory_queue', 'longtext DEFAULT NULL');
  bsm_ensure_job_column('scanned_directories', 'int NOT NULL DEFAULT 0');
  bsm_ensure_job_column('created_categories', 'int NOT NULL DEFAULT 0');
  bsm_ensure_job_column('recent_log', 'longtext DEFAULT NULL');
  bsm_ensure_job_column('metadata_mode', 'varchar(20) NOT NULL DEFAULT "new_only"');
}

function bsm_ensure_job_column($column_name, $definition)
{
  $query = '
SHOW COLUMNS FROM ' . BSM_JOBS_TABLE . ' LIKE "' . pwg_db_real_escape_string($column_name) . '"
;';
  $result = pwg_query($query);

  if (!pwg_db_fetch_assoc($result))
  {
    $query = '
ALTER TABLE ' . BSM_JOBS_TABLE . '
  ADD COLUMN ' . $column_name . ' ' . $definition . '
;';
    pwg_query($query);
  }
}

function bsm_get_post_int($key, $default = 0)
{
  if (!isset($_POST[$key]))
  {
    return (int) $default;
  }

  return (int) $_POST[$key];
}

function bsm_normalize_path($path)
{
  $path = str_replace('\\', '/', trim((string) $path));

  if ($path === '' || $path === '.')
  {
    return $path;
  }

  $normalized = preg_replace('#/+#', '/', $path);
  $normalized = preg_replace('#/$#', '', $normalized);

  if ($normalized === '')
  {
    return '/';
  }

  return $normalized;
}

function bsm_json_encode($value)
{
  $json = json_encode($value);
  return $json === false ? '[]' : $json;
}

function bsm_json_decode_array($value)
{
  if (empty($value))
  {
    return array();
  }

  $decoded = json_decode($value, true);
  return is_array($decoded) ? $decoded : array();
}

function bsm_push_log_entry($entries, $message)
{
  $entries = is_array($entries) ? $entries : array();
  array_unshift(
    $entries,
    array(
      'time' => date('H:i:s'),
      'message' => $message,
    )
  );

  if (count($entries) > BSM_LOG_LIMIT)
  {
    $entries = array_slice($entries, 0, BSM_LOG_LIMIT);
  }

  return $entries;
}

function bsm_get_local_sites()
{
  $query = '
SELECT id, galleries_url
  FROM ' . SITES_TABLE . '
  ORDER BY id ASC
;';
  $result = pwg_query($query);
  $sites = array();

  while ($row = pwg_db_fetch_assoc($result))
  {
    if (url_is_remote($row['galleries_url']))
    {
      continue;
    }

    $sites[] = array(
      'id' => (int) $row['id'],
      'galleries_url' => $row['galleries_url'],
      'label' => sprintf(l10n('Local site #%d'), (int) $row['id']) . ' - ' . $row['galleries_url'],
    );
  }

  return $sites;
}

function bsm_get_site_url($site_id)
{
  $query = '
SELECT galleries_url
  FROM ' . SITES_TABLE . '
  WHERE id = ' . (int) $site_id . '
  LIMIT 1
;';
  $result = pwg_query($query);
  $row = pwg_db_fetch_assoc($result);

  if (!$row || url_is_remote($row['galleries_url']))
  {
    return null;
  }

  return $row['galleries_url'];
}

function bsm_get_category_name_map($site_id)
{
  $query = '
SELECT id, name
  FROM ' . CATEGORIES_TABLE . '
  WHERE site_id = ' . (int) $site_id . '
;';
  $result = pwg_query($query);
  $name_map = array();

  while ($row = pwg_db_fetch_assoc($result))
  {
    $name_map[(int) $row['id']] = $row['name'];
  }

  return $name_map;
}

function bsm_get_root_categories($site_id)
{
  $name_map = bsm_get_category_name_map($site_id);

  $query = '
SELECT id, name, uppercats, global_rank
  FROM ' . CATEGORIES_TABLE . '
  WHERE dir IS NOT NULL
    AND site_id = ' . (int) $site_id . '
  ORDER BY global_rank ASC
;';
  $result = pwg_query($query);
  $categories = array();

  while ($row = pwg_db_fetch_assoc($result))
  {
    $categories[] = array(
      'id' => (int) $row['id'],
      'name' => $row['name'],
      'full_name' => bsm_build_category_full_name($row['uppercats'], $name_map),
    );
  }

  return $categories;
}

function bsm_build_category_full_name($uppercats, $name_map)
{
  $parts = array();

  foreach (explode(',', $uppercats) as $category_id)
  {
    $category_id = (int) $category_id;
    if ($category_id > 0 && isset($name_map[$category_id]))
    {
      $parts[] = $name_map[$category_id];
    }
  }

  return empty($parts) ? '' : implode(' / ', $parts);
}

function bsm_get_sync_categories($site_id, $root_category_id)
{
  $site_id = (int) $site_id;
  $root_category_id = (int) $root_category_id;
  $name_map = bsm_get_category_name_map($site_id);

  $query = '
SELECT id, name, uppercats, global_rank
  FROM ' . CATEGORIES_TABLE . '
  WHERE dir IS NOT NULL
    AND site_id = ' . $site_id . '
';

  if ($root_category_id > 0)
  {
    $query .= '
    AND (
      id = ' . $root_category_id . '
      OR uppercats ' . DB_REGEX_OPERATOR . ' \'(^|,)' . $root_category_id . '(,|$)\'
    )
';
  }

  $query .= '
  ORDER BY global_rank ASC
;';

  $result = pwg_query($query);
  $categories = array();

  while ($row = pwg_db_fetch_assoc($result))
  {
    $categories[] = array(
      'id' => (int) $row['id'],
      'name' => $row['name'],
      'full_name' => bsm_build_category_full_name($row['uppercats'], $name_map),
    );
  }

  return $categories;
}

function bsm_get_scan_basedir($site_id, $root_category_id)
{
  $site_url = bsm_get_site_url($site_id);

  if ($site_url === null)
  {
    return null;
  }

  if ((int) $root_category_id <= 0)
  {
    return bsm_normalize_path(preg_replace('#/*$#', '', $site_url));
  }

  $query = '
SELECT id
  FROM ' . CATEGORIES_TABLE . '
  WHERE id = ' . (int) $root_category_id . '
    AND dir IS NOT NULL
    AND site_id = ' . (int) $site_id . '
  LIMIT 1
;';
  $result = pwg_query($query);

  if (!pwg_db_fetch_assoc($result))
  {
    return null;
  }

  $fulldirs = get_fulldirs(array((int) $root_category_id));

  if (empty($fulldirs[$root_category_id]))
  {
    return null;
  }

  return bsm_normalize_path($fulldirs[$root_category_id]);
}

function bsm_list_immediate_subdirectories($path, &$readable)
{
  $readable = false;
  $subdirs = array();

  if (!is_dir($path))
  {
    return $subdirs;
  }

  $contents = @opendir($path);
  if ($contents === false)
  {
    return $subdirs;
  }

  $readable = true;

  while (($node = readdir($contents)) !== false)
  {
    if ($node === '.' || $node === '..')
    {
      continue;
    }

    if ($node === 'pwg_high' || $node === 'pwg_representative' || $node === 'pwg_format' || $node === 'thumbnail')
    {
      continue;
    }

    $full_path = bsm_normalize_path($path . '/' . $node);
    if (is_dir($full_path))
    {
      $subdirs[] = $full_path;
    }
  }

  closedir($contents);
  natcasesort($subdirs);

  return array_values($subdirs);
}

function bsm_load_category_context($site_id)
{
  $query = '
SELECT id, id_uppercat, uppercats, global_rank, status, visible
  FROM ' . CATEGORIES_TABLE . '
  WHERE dir IS NOT NULL
    AND site_id = ' . (int) $site_id . '
;';
  $result = pwg_query($query);
  $db_categories = array();
  $category_ids = array();

  while ($row = pwg_db_fetch_assoc($result))
  {
    $category_id = (int) $row['id'];
    $category_ids[] = $category_id;
    $db_categories[$category_id] = array(
      'id' => $category_id,
      'parent' => empty($row['id_uppercat']) ? null : (int) $row['id_uppercat'],
      'status' => $row['status'],
      'visible' => $row['visible'],
      'uppercats' => $row['uppercats'],
      'global_rank' => $row['global_rank'],
    );
  }

  $db_fulldirs = array();
  if (!empty($category_ids))
  {
    $fulldirs = get_fulldirs($category_ids);
    foreach ($fulldirs as $category_id => $fulldir)
    {
      $db_fulldirs[bsm_normalize_path($fulldir)] = (int) $category_id;
    }
  }

  $next_rank = array('NULL' => 1);
  foreach ($category_ids as $category_id)
  {
    $next_rank[$category_id] = 1;
  }

  $query = '
SELECT id_uppercat, MAX(`rank`) + 1 AS next_rank
  FROM ' . CATEGORIES_TABLE . '
  GROUP BY id_uppercat
;';
  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result))
  {
    $parent_id = (!isset($row['id_uppercat']) || $row['id_uppercat'] === '') ? 'NULL' : (int) $row['id_uppercat'];
    $next_rank[$parent_id] = (int) $row['next_rank'];
  }

  return array(
    'db_categories' => $db_categories,
    'db_fulldirs' => $db_fulldirs,
    'next_rank' => $next_rank,
    'next_id' => pwg_db_nextval('id', CATEGORIES_TABLE),
  );
}

function bsm_insert_categories(&$context, $site_id, $fulldirs)
{
  global $conf;

  $result = array(
    'inserted' => 0,
    'invalid_paths' => array(),
  );

  if (empty($fulldirs))
  {
    return $result;
  }

  $inserts = array();
  foreach ($fulldirs as $fulldir)
  {
    $fulldir = bsm_normalize_path($fulldir);

    if (isset($context['db_fulldirs'][$fulldir]))
    {
      continue;
    }

    $dir = basename($fulldir);
    if (!preg_match($conf['sync_chars_regex'], $dir))
    {
      $result['invalid_paths'][] = $fulldir;
      continue;
    }

    $insert = array(
      'id' => $context['next_id']++,
      'dir' => $dir,
      'name' => str_replace('_', ' ', $dir),
      'site_id' => (int) $site_id,
      'commentable' => boolean_to_string($conf['newcat_default_commentable']),
      'status' => $conf['newcat_default_status'],
      'visible' => boolean_to_string($conf['newcat_default_visible']),
    );

    $parent_path = bsm_normalize_path(dirname($fulldir));
    if (isset($context['db_fulldirs'][$parent_path]))
    {
      $parent_id = $context['db_fulldirs'][$parent_path];
      $insert['id_uppercat'] = $parent_id;
      $insert['uppercats'] = $context['db_categories'][$parent_id]['uppercats'] . ',' . $insert['id'];
      $insert['rank'] = $context['next_rank'][$parent_id]++;
      $insert['global_rank'] = $context['db_categories'][$parent_id]['global_rank'] . '.' . $insert['rank'];

      if ($context['db_categories'][$parent_id]['status'] === 'private')
      {
        $insert['status'] = 'private';
      }
      if ($context['db_categories'][$parent_id]['visible'] === 'false')
      {
        $insert['visible'] = 'false';
      }
    }
    else
    {
      $insert['uppercats'] = $insert['id'];
      $insert['rank'] = $context['next_rank']['NULL']++;
      $insert['global_rank'] = $insert['rank'];
    }

    $inserts[] = $insert;

    $context['db_categories'][$insert['id']] = array(
      'id' => $insert['id'],
      'parent' => isset($insert['id_uppercat']) ? (int) $insert['id_uppercat'] : null,
      'status' => $insert['status'],
      'visible' => $insert['visible'],
      'uppercats' => $insert['uppercats'],
      'global_rank' => $insert['global_rank'],
    );
    $context['db_fulldirs'][$fulldir] = $insert['id'];
    $context['next_rank'][$insert['id']] = 1;
  }

  if (empty($inserts))
  {
    return $result;
  }

  mass_inserts(
    CATEGORIES_TABLE,
    array('id', 'dir', 'name', 'site_id', 'id_uppercat', 'uppercats', 'commentable', 'visible', 'status', 'rank', 'global_rank'),
    $inserts
  );

  $category_ids = array();
  $category_up = array();
  foreach ($inserts as $insert)
  {
    $category_ids[] = (int) $insert['id'];
    if (!empty($insert['id_uppercat']))
    {
      $category_up[] = (int) $insert['id_uppercat'];
    }
  }

  pwg_activity('album', $category_ids, 'add', array('sync' => true));

  if ($conf['inheritance_by_default'] && !empty($category_up))
  {
    $category_up = implode(',', array_unique($category_up));

    $granted_grps = array();
    $query = '
SELECT *
  FROM ' . GROUP_ACCESS_TABLE . '
  WHERE cat_id IN (' . $category_up . ')
;';
    $result_query = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result_query))
    {
      $cat_id = (int) $row['cat_id'];
      if (!isset($granted_grps[$cat_id]))
      {
        $granted_grps[$cat_id] = array();
      }
      $granted_grps[$cat_id][] = (int) $row['group_id'];
    }

    $granted_users = array();
    $query = '
SELECT *
  FROM ' . USER_ACCESS_TABLE . '
  WHERE cat_id IN (' . $category_up . ')
;';
    $result_query = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result_query))
    {
      $cat_id = (int) $row['cat_id'];
      if (!isset($granted_users[$cat_id]))
      {
        $granted_users[$cat_id] = array();
      }
      $granted_users[$cat_id][] = (int) $row['user_id'];
    }

    $insert_granted_users = array();
    $insert_granted_grps = array();

    foreach ($category_ids as $category_id)
    {
      $parent_id = $context['db_categories'][$category_id]['parent'];
      while ($parent_id !== null && in_array($parent_id, $category_ids))
      {
        $parent_id = $context['db_categories'][$parent_id]['parent'];
      }

      if ($context['db_categories'][$category_id]['status'] === 'private' && $parent_id !== null)
      {
        if (isset($granted_grps[$parent_id]))
        {
          foreach ($granted_grps[$parent_id] as $group_id)
          {
            $insert_granted_grps[] = array(
              'group_id' => $group_id,
              'cat_id' => $category_id,
            );
          }
        }

        if (isset($granted_users[$parent_id]))
        {
          foreach ($granted_users[$parent_id] as $user_id)
          {
            $insert_granted_users[] = array(
              'user_id' => $user_id,
              'cat_id' => $category_id,
            );
          }
        }
      }
    }

    if (!empty($insert_granted_grps))
    {
      mass_inserts(GROUP_ACCESS_TABLE, array('group_id', 'cat_id'), $insert_granted_grps);
    }

    if (!empty($insert_granted_users))
    {
      $insert_granted_users = array_unique($insert_granted_users, SORT_REGULAR);
      mass_inserts(USER_ACCESS_TABLE, array('user_id', 'cat_id'), $insert_granted_users);
    }
  }
  else
  {
    add_permission_on_category($category_ids, get_admins());
  }

  $result['inserted'] = count($inserts);

  return $result;
}

function bsm_get_local_site_reader($site_id)
{
  $site_url = bsm_get_site_url($site_id);
  if ($site_url === null)
  {
    return null;
  }

  include_once(PHPWG_ROOT_PATH . 'admin/site_reader_local.php');

  $site_reader = new LocalSiteReader($site_url);
  if (!$site_reader->open())
  {
    return null;
  }

  return $site_reader;
}

function bsm_get_category_fulldir($category_id)
{
  $category_id = (int) $category_id;
  if ($category_id <= 0)
  {
    return null;
  }

  $fulldirs = get_fulldirs(array($category_id));
  if (empty($fulldirs[$category_id]))
  {
    return null;
  }

  return bsm_normalize_path($fulldirs[$category_id]);
}

function bsm_get_direct_fs_elements($site_reader, $path)
{
  global $conf;

  $fs = array();

  if (!is_dir($path))
  {
    return $fs;
  }

  $contents = @opendir($path);
  if ($contents === false)
  {
    return $fs;
  }

  while (($node = readdir($contents)) !== false)
  {
    if ($node === '.' || $node === '..')
    {
      continue;
    }

    $file_path = $path . '/' . $node;
    if (!is_file($file_path))
    {
      continue;
    }

    $extension = strtolower(get_extension($node));
    $filename_wo_ext = get_filename_wo_extension($node);

    if (!isset($conf['flip_file_ext'][$extension]))
    {
      continue;
    }

    $representative_ext = null;
    if (!isset($conf['flip_picture_ext'][$extension]))
    {
      $representative_ext = $site_reader->get_representative_ext($path, $filename_wo_ext);
    }

    $fs[$file_path] = array(
      'representative_ext' => $representative_ext,
    );

    if ($conf['enable_formats'])
    {
      $fs[$file_path]['formats'] = $site_reader->get_formats($path, $filename_wo_ext);
    }
  }

  closedir($contents);
  ksort($fs);

  return $fs;
}

function bsm_get_db_elements_for_category($category_id)
{
  $query = '
SELECT id, path
  FROM ' . IMAGES_TABLE . '
  WHERE storage_category_id = ' . (int) $category_id . '
;';
  $result = pwg_query($query);
  $db_elements = array();

  while ($row = pwg_db_fetch_assoc($result))
  {
    $db_elements[(int) $row['id']] = $row['path'];
  }

  return $db_elements;
}

function bsm_sync_category_files($site_id, $category_id, $metadata_mode)
{
  global $conf, $user;

  $summary = array(
    'new_elements' => 0,
    'del_elements' => 0,
    'upd_elements' => 0,
    'metadata_elements' => 0,
    'errors' => 0,
  );

  $site_reader = bsm_get_local_site_reader($site_id);
  $category_path = bsm_get_category_fulldir($category_id);

  if ($site_reader === null || $category_path === null || !is_dir($category_path))
  {
    $summary['errors']++;
    return $summary;
  }

  $fs = bsm_get_direct_fs_elements($site_reader, $category_path);
  $db_elements = bsm_get_db_elements_for_category($category_id);
  $next_element_id = pwg_db_nextval('id', IMAGES_TABLE);

  $inserts = array();
  $insert_links = array();
  $insert_formats = array();
  $formats_to_delete = array();
  $caddiables = array();

  foreach (array_diff(array_keys($fs), $db_elements) as $path)
  {
    $filename = basename($path);
    if (!preg_match($conf['sync_chars_regex'], $filename))
    {
      $summary['errors']++;
      continue;
    }

    $insert = array(
      'id' => $next_element_id++,
      'file' => pwg_db_real_escape_string($filename),
      'name' => pwg_db_real_escape_string(get_name_from_file($filename)),
      'date_available' => CURRENT_DATE,
      'path' => pwg_db_real_escape_string($path),
      'representative_ext' => $fs[$path]['representative_ext'],
      'storage_category_id' => (int) $category_id,
      'added_by' => $user['id'],
    );

    $inserts[] = $insert;
    $insert_links[] = array(
      'image_id' => $insert['id'],
      'category_id' => $insert['storage_category_id'],
    );
    $caddiables[] = $insert['id'];

    if ($conf['enable_formats'] && !empty($fs[$path]['formats']))
    {
      foreach ($fs[$path]['formats'] as $ext => $filesize)
      {
        $insert_formats[] = array(
          'image_id' => $insert['id'],
          'ext' => $ext,
          'filesize' => $filesize,
        );
      }
    }
  }

  if ($conf['enable_formats'])
  {
    $db_elements_flip = array_flip($db_elements);
    $existing_ids = array();

    foreach (array_intersect_key($fs, $db_elements_flip) as $path => $existing)
    {
      $existing_ids[] = $db_elements_flip[$path];
    }

    if (!empty($existing_ids))
    {
      $db_formats = array();
      $query = '
SELECT *
  FROM ' . IMAGE_FORMAT_TABLE . '
  WHERE image_id IN (' . implode(',', array_map('intval', $existing_ids)) . ')
;';
      $result = pwg_query($query);

      while ($row = pwg_db_fetch_assoc($result))
      {
        $image_id = (int) $row['image_id'];
        if (!isset($db_formats[$image_id]))
        {
          $db_formats[$image_id] = array();
        }
        $db_formats[$image_id][$row['ext']] = (int) $row['format_id'];
      }

      foreach ($db_formats as $image_id => $formats)
      {
        $path = $db_elements[$image_id];
        $current_formats = isset($fs[$path]['formats']) ? $fs[$path]['formats'] : array();
        $image_formats_to_delete = array_diff_key($formats, $current_formats);

        foreach ($image_formats_to_delete as $ext => $format_id)
        {
          $formats_to_delete[] = $format_id;
        }
      }

      foreach ($existing_ids as $image_id)
      {
        $path = $db_elements[$image_id];
        $existing_formats = isset($db_formats[$image_id]) ? $db_formats[$image_id] : array();
        $current_formats = isset($fs[$path]['formats']) ? $fs[$path]['formats'] : array();
        $image_formats_to_insert = array_diff_key($current_formats, $existing_formats);

        foreach ($image_formats_to_insert as $ext => $filesize)
        {
          $insert_formats[] = array(
            'image_id' => $image_id,
            'ext' => $ext,
            'filesize' => $filesize,
          );
        }
      }
    }
  }

  if (!empty($inserts))
  {
    mass_inserts(IMAGES_TABLE, array_keys($inserts[0]), $inserts);
    mass_inserts(IMAGE_CATEGORY_TABLE, array_keys($insert_links[0]), $insert_links);
    pwg_activity('photo', $caddiables, 'add', array('sync' => true));
  }

  if (!empty($insert_formats))
  {
    mass_inserts(IMAGE_FORMAT_TABLE, array_keys($insert_formats[0]), $insert_formats);
  }

  if (!empty($formats_to_delete))
  {
    $query = '
DELETE
  FROM ' . IMAGE_FORMAT_TABLE . '
  WHERE format_id IN (' . implode(',', array_map('intval', $formats_to_delete)) . ')
;';
    pwg_query($query);
  }

  $summary['new_elements'] = count($inserts);

  $to_delete_elements = array();
  foreach (array_diff($db_elements, array_keys($fs)) as $path)
  {
    $element_id = array_search($path, $db_elements);
    if ($element_id !== false)
    {
      $to_delete_elements[] = (int) $element_id;
    }
  }

  if (!empty($to_delete_elements))
  {
    delete_elements($to_delete_elements);
  }

  $summary['del_elements'] = count($to_delete_elements);

  $files = get_filelist($category_id, $site_id, false, false);
  $datas = array();

  foreach ($files as $id => $file)
  {
    $file = $file['path'];
    $data = $site_reader->get_element_update_attributes($file);
    if (!is_array($data))
    {
      continue;
    }

    $data['id'] = $id;
    $datas[] = $data;
  }

  if (!empty($datas))
  {
    mass_updates(
      IMAGES_TABLE,
      array(
        'primary' => array('id'),
        'update' => $site_reader->get_update_attributes(),
      ),
      $datas
    );
  }

  $summary['upd_elements'] = count($datas);

  $only_new_metadata = $metadata_mode !== 'all';
  $metadata_files = get_filelist($category_id, $site_id, false, $only_new_metadata);
  $metadata_updates = array();
  $tags_of = array();

  foreach ($metadata_files as $id => $element_infos)
  {
    $data = $site_reader->get_element_metadata($element_infos);
    if (!is_array($data))
    {
      $summary['errors']++;
      continue;
    }

    $data['date_metadata_update'] = CURRENT_DATE;
    $data['id'] = $id;
    $metadata_updates[] = $data;

    foreach (array('keywords', 'tags') as $key)
    {
      if (!isset($data[$key]))
      {
        continue;
      }

      if (!isset($tags_of[$id]))
      {
        $tags_of[$id] = array();
      }

      foreach (explode(',', $data[$key]) as $tag_name)
      {
        $tags_of[$id][] = tag_id_from_tag_name($tag_name);
      }
    }
  }

  if (!empty($metadata_updates))
  {
    mass_updates(
      IMAGES_TABLE,
      array(
        'primary' => array('id'),
        'update' => array_unique(
          array_merge(
            array_diff(
              $site_reader->get_metadata_attributes(),
              array('keywords', 'tags')
            ),
            array('date_metadata_update')
          )
        ),
      ),
      $metadata_updates,
      MASS_UPDATES_SKIP_EMPTY
    );
  }

  if (!empty($tags_of))
  {
    set_tags_of($tags_of);
  }

  $summary['metadata_elements'] = count($metadata_updates);

  return $summary;
}

function bsm_cancel_active_jobs()
{
  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));
  $query = '
SELECT *
  FROM ' . BSM_JOBS_TABLE . '
  WHERE status IN ("running", "queued")
;';
  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result))
  {
    $job = bsm_normalize_job_row($row);
    $entries = bsm_push_log_entry($job['recent_log_entries'], l10n('Job cancelled'));

    bsm_update_job_state(
      $job['id'],
      array(
        'status' => 'cancelled',
        'current_message' => l10n('Job cancelled'),
        'current_category_id' => 'NULL',
        'current_category_name' => 'NULL',
        'directory_queue' => bsm_json_encode(array()),
        'recent_log' => bsm_json_encode($entries),
        'finished_on' => $dbnow,
      )
    );
  }
}

function bsm_get_active_job()
{
  $query = '
SELECT *
  FROM ' . BSM_JOBS_TABLE . '
  WHERE status IN ("running", "queued")
  ORDER BY id DESC
  LIMIT 1
;';
  $result = pwg_query($query);
  $row = pwg_db_fetch_assoc($result);

  return $row ? bsm_normalize_job_row($row) : null;
}

function bsm_get_job($job_id)
{
  $query = '
SELECT *
  FROM ' . BSM_JOBS_TABLE . '
  WHERE id = ' . (int) $job_id . '
  LIMIT 1
;';
  $result = pwg_query($query);
  $row = pwg_db_fetch_assoc($result);

  return $row ? bsm_normalize_job_row($row) : null;
}

function bsm_normalize_job_row($row)
{
  $row['id'] = (int) $row['id'];
  $row['site_id'] = (int) $row['site_id'];
  $row['root_category_id'] = empty($row['root_category_id']) ? 0 : (int) $row['root_category_id'];
  $row['total_categories'] = (int) $row['total_categories'];
  $row['processed_categories'] = (int) $row['processed_categories'];
  $row['current_category_id'] = empty($row['current_category_id']) ? 0 : (int) $row['current_category_id'];
  $row['scanned_directories'] = empty($row['scanned_directories']) ? 0 : (int) $row['scanned_directories'];
  $row['created_categories'] = empty($row['created_categories']) ? 0 : (int) $row['created_categories'];
  $row['metadata_mode'] = (isset($row['metadata_mode']) && $row['metadata_mode'] === 'all') ? 'all' : 'new_only';
  $row['directory_queue_entries'] = bsm_json_decode_array(isset($row['directory_queue']) ? $row['directory_queue'] : '');
  $row['remaining_directories'] = count($row['directory_queue_entries']);
  $row['recent_log_entries'] = bsm_json_decode_array(isset($row['recent_log']) ? $row['recent_log'] : '');
  unset($row['directory_queue']);
  unset($row['recent_log']);

  return $row;
}

function bsm_prepare_job_for_output($job)
{
  if (!$job || !is_array($job))
  {
    return $job;
  }

  unset($job['directory_queue_entries']);

  return $job;
}

function bsm_start_job($site_id, $root_category_id, $metadata_mode = 'new_only')
{
  $site_id = (int) $site_id;
  $root_category_id = (int) $root_category_id;
  $metadata_mode = $metadata_mode === 'all' ? 'all' : 'new_only';

  if ($site_id <= 0)
  {
    return null;
  }

  $basedir = bsm_get_scan_basedir($site_id, $root_category_id);
  if ($basedir === null || !is_dir($basedir))
  {
    return null;
  }

  bsm_cancel_active_jobs();

  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));
  $log_entries = array();
  $log_entries = bsm_push_log_entry($log_entries, l10n('Bulk job started'));
  $log_entries = bsm_push_log_entry(
    $log_entries,
    $metadata_mode === 'all'
      ? l10n('Metadata mode: re-read all metadata')
      : l10n('Metadata mode: only read metadata for new files')
  );
  $log_entries = bsm_push_log_entry(
    $log_entries,
    sprintf(l10n('Directory scan prepared for %s'), $basedir)
  );

  $query = '
INSERT INTO ' . BSM_JOBS_TABLE . '
  (status, phase, site_id, root_category_id, total_categories, processed_categories, current_message, metadata_mode, directory_queue, scanned_directories, created_categories, recent_log, created_on, updated_on)
VALUES
  (
    "running",
    "directory_scan",
    ' . $site_id . ',
    ' . ($root_category_id > 0 ? $root_category_id : 'NULL') . ',
    0,
    0,
    "' . pwg_db_real_escape_string(l10n('Waiting to start directory scan')) . '",
    "' . pwg_db_real_escape_string($metadata_mode) . '",
    "' . pwg_db_real_escape_string(bsm_json_encode(array($basedir))) . '",
    0,
    0,
    "' . pwg_db_real_escape_string(bsm_json_encode($log_entries)) . '",
    "' . $dbnow . '",
    "' . $dbnow . '"
  )
;';
  pwg_query($query);

  return bsm_get_active_job();
}

function bsm_get_next_step_payload($job)
{
  if (!$job)
  {
    return array(
      'job' => null,
      'payload' => array('done' => true),
    );
  }

  if ($job['status'] !== 'running')
  {
    return array(
      'job' => $job,
      'payload' => array('done' => true),
    );
  }

  if ($job['phase'] === 'directory_scan')
  {
    $job = bsm_process_directory_scan_batch($job['id']);

    if (!$job || $job['status'] !== 'running')
    {
      return array(
        'job' => $job,
        'payload' => array('done' => true),
      );
    }

    if ($job['phase'] === 'directory_scan')
    {
      return array(
        'job' => $job,
        'payload' => array(
          'done' => false,
          'step' => array(
            'type' => 'internal',
            'label' => $job['current_message'],
          ),
        ),
      );
    }
  }

  $job = bsm_process_file_sync_step($job['id']);

  if (!$job || $job['status'] !== 'running')
  {
    return array(
      'job' => $job,
      'payload' => array('done' => true),
    );
  }

  return array(
    'job' => $job,
    'payload' => array(
      'done' => false,
      'step' => array(
        'type' => 'internal',
        'label' => $job['current_message'],
      ),
    ),
  );
}

function bsm_process_directory_scan_batch($job_id)
{
  $job = bsm_get_job($job_id);

  if (!$job || $job['status'] !== 'running' || $job['phase'] !== 'directory_scan')
  {
    return $job;
  }

  $queue = $job['directory_queue_entries'];
  if (empty($queue))
  {
    return bsm_finish_directory_scan($job);
  }

  $context = bsm_load_category_context($job['site_id']);
  $known_queue = array_fill_keys($queue, true);
  $new_fulldirs = array();
  $batch_count = 0;
  $entries = $job['recent_log_entries'];
  $scanned_directories = $job['scanned_directories'];
  $created_categories = $job['created_categories'];

  while ($batch_count < BSM_SCAN_BATCH_SIZE && !empty($queue))
  {
    $current_path = array_shift($queue);
    unset($known_queue[$current_path]);
    $batch_count++;
    $scanned_directories++;

    $readable = false;
    $subdirs = bsm_list_immediate_subdirectories($current_path, $readable);

    if (!$readable)
    {
      $entries = bsm_push_log_entry(
        $entries,
        sprintf(l10n('Directory could not be read: %s'), $current_path)
      );
      continue;
    }

    foreach ($subdirs as $subdir)
    {
      if (!isset($context['db_fulldirs'][$subdir]))
      {
        $new_fulldirs[] = $subdir;
      }

      if (!isset($known_queue[$subdir]))
      {
        $queue[] = $subdir;
        $known_queue[$subdir] = true;
      }
    }
  }

  $insert_result = bsm_insert_categories($context, $job['site_id'], $new_fulldirs);
  $created_categories += $insert_result['inserted'];

  if (!empty($insert_result['invalid_paths']))
  {
    $entries = bsm_push_log_entry(
      $entries,
      sprintf(
        l10n('Skipped %d directories because their names do not match sync rules'),
        count($insert_result['invalid_paths'])
      )
    );
  }

  $summary = sprintf(
    l10n('Scanned %d directories in this batch, %d directories remaining, %d albums created so far'),
    $batch_count,
    count($queue),
    $created_categories
  );
  $entries = bsm_push_log_entry($entries, $summary);

  bsm_update_job_state(
    $job['id'],
    array(
      'directory_queue' => bsm_json_encode($queue),
      'scanned_directories' => $scanned_directories,
      'created_categories' => $created_categories,
      'current_category_id' => 'NULL',
      'current_category_name' => 'NULL',
      'current_message' => $summary,
      'recent_log' => bsm_json_encode($entries),
    )
  );

  $job = bsm_get_job($job['id']);

  if (empty($queue))
  {
    return bsm_finish_directory_scan($job);
  }

  return $job;
}

function bsm_finish_directory_scan($job)
{
  if (!$job)
  {
    return null;
  }

  $categories = bsm_get_sync_categories($job['site_id'], $job['root_category_id']);
  $total_categories = count($categories);
  $entries = $job['recent_log_entries'];

  if ($total_categories === 0)
  {
    bsm_mark_job_finished($job['id'], l10n('No albums found for synchronization'), $entries);
    return bsm_get_job($job['id']);
  }

  $message = sprintf(
    l10n('Directory scan finished after %d scanned directories, %d albums are now queued'),
    $job['scanned_directories'],
    $total_categories
  );
  $entries = bsm_push_log_entry($entries, $message);

  bsm_update_job_state(
    $job['id'],
    array(
      'phase' => 'file_sync',
      'total_categories' => $total_categories,
      'processed_categories' => 0,
      'directory_queue' => bsm_json_encode(array()),
      'current_category_id' => 'NULL',
      'current_category_name' => 'NULL',
      'current_message' => l10n('Directory scan finished, starting file synchronization'),
      'recent_log' => bsm_json_encode($entries),
    )
  );

  return bsm_get_job($job['id']);
}

function bsm_build_native_sync_step($job)
{
  return array(
    'job' => $job,
    'payload' => array('done' => true),
  );
}

function bsm_process_file_sync_step($job_id)
{
  $job = bsm_get_job($job_id);

  if (!$job || $job['status'] !== 'running')
  {
    return $job;
  }

  $categories = bsm_get_sync_categories($job['site_id'], $job['root_category_id']);
  $total_categories = count($categories);

  if ($job['processed_categories'] >= $total_categories)
  {
    bsm_mark_job_finished($job['id'], l10n('Bulk sync finished'));
    update_category('all');
    return bsm_get_job($job['id']);
  }

  $current = $categories[$job['processed_categories']];
  $entries = bsm_push_log_entry(
    $job['recent_log_entries'],
    l10n('Starting') . ': ' . $current['full_name']
  );

  bsm_update_job_state(
    $job['id'],
    array(
      'total_categories' => $total_categories,
      'current_category_id' => $current['id'],
      'current_category_name' => $current['full_name'],
      'current_message' => l10n('Synchronizing album') . ': ' . $current['full_name'],
      'recent_log' => bsm_json_encode($entries),
    )
  );

  $summary = bsm_sync_category_files($job['site_id'], $current['id'], $job['metadata_mode']);
  $processed_categories = $job['processed_categories'] + 1;

  $done_message = sprintf(
    l10n('Finished album summary: %s (%d new, %d removed, %d updated, %d metadata, %d errors)'),
    $current['full_name'],
    $summary['new_elements'],
    $summary['del_elements'],
    $summary['upd_elements'],
    $summary['metadata_elements'],
    $summary['errors']
  );
  $entries = bsm_push_log_entry($entries, $done_message);

  if ($processed_categories >= $total_categories)
  {
    bsm_update_job_state(
      $job['id'],
      array(
        'processed_categories' => $processed_categories,
        'total_categories' => $total_categories,
        'current_category_id' => 'NULL',
        'current_category_name' => 'NULL',
        'current_message' => l10n('Bulk sync finished'),
        'recent_log' => bsm_json_encode($entries),
      )
    );
    bsm_mark_job_finished($job['id'], l10n('Bulk sync finished'));
    update_category('all');
    return bsm_get_job($job['id']);
  }

  bsm_update_job_state(
    $job['id'],
    array(
      'processed_categories' => $processed_categories,
      'total_categories' => $total_categories,
      'current_category_id' => 'NULL',
      'current_category_name' => 'NULL',
      'current_message' => l10n('Waiting for next batch'),
      'recent_log' => bsm_json_encode($entries),
    )
  );

  return bsm_get_job($job['id']);
}

function bsm_complete_step($job_id)
{
  $job = bsm_get_job($job_id);

  if (!$job || $job['status'] !== 'running')
  {
    return $job;
  }

  $processed_categories = $job['processed_categories'] + 1;
  $categories = bsm_get_sync_categories($job['site_id'], $job['root_category_id']);
  $total_categories = count($categories);
  $entries = $job['recent_log_entries'];

  if (!empty($job['current_category_name']))
  {
    $entries = bsm_push_log_entry(
      $entries,
      l10n('Finished') . ': ' . $job['current_category_name']
    );
  }

  if ($processed_categories >= $total_categories)
  {
    bsm_mark_job_finished($job['id'], l10n('Bulk sync finished'), $entries);
    return bsm_get_job($job['id']);
  }

  bsm_update_job_state(
    $job['id'],
    array(
      'processed_categories' => $processed_categories,
      'total_categories' => $total_categories,
      'current_category_id' => 'NULL',
      'current_category_name' => 'NULL',
      'current_message' => l10n('Waiting for next batch'),
      'recent_log' => bsm_json_encode($entries),
    )
  );

  return bsm_get_job($job['id']);
}

function bsm_update_job_state($job_id, $fields)
{
  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));
  $updates = array('updated_on = "' . $dbnow . '"');

  foreach ($fields as $field => $value)
  {
    if ($value === 'NULL')
    {
      $updates[] = $field . ' = NULL';
    }
    elseif (is_int($value))
    {
      $updates[] = $field . ' = ' . $value;
    }
    else
    {
      $updates[] = $field . ' = "' . pwg_db_real_escape_string($value) . '"';
    }
  }

  $query = '
UPDATE ' . BSM_JOBS_TABLE . '
  SET ' . implode(",\n      ", $updates) . '
  WHERE id = ' . (int) $job_id . '
;';
  pwg_query($query);
}

function bsm_mark_job_finished($job_id, $message, $entries = null)
{
  $job = bsm_get_job($job_id);
  if (!$job)
  {
    return;
  }

  if ($entries === null)
  {
    $entries = $job['recent_log_entries'];
  }

  $entries = bsm_push_log_entry($entries, $message);
  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW();'));

  bsm_update_job_state(
    $job_id,
    array(
      'status' => 'completed',
      'current_message' => $message,
      'current_category_id' => 'NULL',
      'current_category_name' => 'NULL',
      'directory_queue' => bsm_json_encode(array()),
      'recent_log' => bsm_json_encode($entries),
      'finished_on' => $dbnow,
    )
  );
}
?>
