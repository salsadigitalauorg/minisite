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
