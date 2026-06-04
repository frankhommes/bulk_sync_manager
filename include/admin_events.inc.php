<?php
defined('BSM_PATH') or die('Hacking attempt!');

function bsm_admin_menu_links($menu)
{
  $menu[] = array(
    'NAME' => l10n('Bulk Sync Manager'),
    'URL' => BSM_ADMIN,
  );

  return $menu;
}
?>
