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

/**
 * Save minisite assets to database.
 */
function minisite_site_save_database($entity_type, $entity, $field, $instance, $langcode, $item, $minisite_listing = []) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
  db_merge('minisite_asset')
    ->key(array(
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'entity_id' => $id,
      'revision_id' => $vid,
      'minisite_field_name' => $field->field_name,
      'minisite_fid' => $item['fid'],
      'language' => $langcode,
      'source' => $id,
    ))
    ->fields(array(
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'entity_id' => $id,
      'revision_id' => $vid,
      'minisite_field_name' => $field->field_name,
      'minisite_fid' => $item['fid'],
      'language' => $langcode,
      'source' => $id,
      'alias' => '',
    ))
    ->execute();
}
