<?php

function plugin_install($plugin_id, $plugin_version, &$errors)
{
  global $prefixeTable;

  $query = '
CREATE TABLE IF NOT EXISTS ' . $prefixeTable . 'bulk_sync_manager_jobs (
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
}

function plugin_activate($plugin_id, $plugin_version, &$errors)
{
  plugin_install($plugin_id, $plugin_version, $errors);
}

function plugin_deactivate($plugin_id)
{
}

function plugin_uninstall()
{
  global $prefixeTable;

  $query = 'DROP TABLE IF EXISTS ' . $prefixeTable . 'bulk_sync_manager_jobs;';
  pwg_query($query);
}
?>
