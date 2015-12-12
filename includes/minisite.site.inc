<?php

/**
 * @file
 * minisite.site.inc
 */

/**
 * Fetch minisite information.
 */
function minisite_site_info($fid, $tree = TRUE) {
  if (empty($fid)) {
    return FALSE;
  }

  $file = file_load($fid);
  if (!$file) {
    return FALSE;
  }

  // Check if minisite is archive.
  $archive = MinisiteArchive::open($file);
  if (!$archive) {
    return FALSE;
  }

  $minisite_tree = $archive->filesTree();
  // Ignore __MACOSX folder.
  unset($minisite_tree['__MACOSX']);

  $root_files = array_keys($minisite_tree);
  $top_folder = $root_files[0];

  $minisite_path = MINISITE_ASSETPATH . '/' . minisite_site_hash($file);

  if ($tree === TRUE) {
    $minisite_listing = $minisite_tree;
  }
  else {
    $minisite_listing = $archive->filesList();
  }

  return array(
    $minisite_path,
    $top_folder,
    $minisite_listing,
  );
}

/**
 * Exact minisite files.
 */
function minisite_site_extract(stdClass $file, $minisite_extract_path = MINISITE_ASSETPATH) {
  $archive = MinisiteArchive::open($file);
  $archive->extract('public://' . $minisite_extract_path);
}

/**
 * Given an alias, return its minisite information if one exists.
 */
function minisite_site_lookup_path($path = NULL, $path_language = NULL) {
  global $language_url;
  $path = $path ? $path : current_path();
  // If current path has a alias.
  $path = drupal_get_path_alias($path);
  $path_language = $path_language ? $path_language : $language_url->language;

  // Prepare query args.
  $args = array(
    ':source' => $path,
    ':language' => $path_language,
    ':language_none' => LANGUAGE_NONE,
  );
  // Find minisite information.
  if ($path_language == LANGUAGE_NONE) {
    unset($args[':language']);
    $result = db_query("SELECT entity_type, bundle, entity_id, minisite_field_name, minisite_fid, language ,source FROM {minisite_asset} WHERE alias = :source AND language = :language_none", $args)->fetchAssoc();
  }
  elseif ($path_language > LANGUAGE_NONE) {
    $result = db_query("SELECT entity_type, bundle, entity_id, minisite_field_name, minisite_fid, language, source FROM {minisite_asset} WHERE alias = :source AND language IN (:language, :language_none) ORDER BY language DESC", $args)->fetchAssoc();
  }
  else {
    $result = db_query("SELECT entity_type, bundle, entity_id, minisite_field_name, minisite_fid, language, source FROM {minisite_asset} WHERE alias = :source AND language IN (:language, :language_none) ORDER BY language ASC", $args)->fetchAssoc();
  }

  if (!empty($result)) {
    return $result;
  }
  else {
    return FALSE;
  }
}

/**
 * Generate minisite site hash.
 */
function minisite_site_hash(stdClass $file) {
  if ($file && isset($file->fid)) {
    $fid = $file->fid;
    return $fid;
  }
  else {
    return '';
  }
}
