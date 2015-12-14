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
 * Return minisite file extensions blacklist.
 */
function minisite_site_extensions_blacklist() {
  $extensions_blacklist = &drupal_static(__FUNCTION__);

  if (!isset($extensions_blacklist)) {
    $extensions_blacklist = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower(MINISITE_EXTENSIONS_BLACKLIST)));
    $extensions_blacklist = array_filter(explode(' ', $extensions_blacklist));
    $extensions_blacklist = array_unique($extensions_blacklist);
  }

  return $extensions_blacklist;
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
 * Parse minisite.
 */
function minisite_site_parse($minisite_info) {
  list($minisite_site_request, $minisite_site_url, $minisite_node_alias) = minisite_site_paths_info($minisite_info);

  // Bypass lib error.
  libxml_use_internal_errors(TRUE);

  $document = new \DOMDocument();
  $document->loadHTMLFile($minisite_site_request);

  // Return 404 if document is empty.
  if (empty($document)) {
    return drupal_not_found();
  }

  $head = $document->getElementsByTagName('head')->item(0);
  // Return 404 is document head is empty.
  if (empty($head)) {
    return drupal_not_found();
  }
  else {
    // Update base href.
    $tag_base = $document->createElement('base');
    $tag_base->setAttribute('href', $minisite_site_url);
    if ($head->hasChildNodes()) {
      $head->insertBefore($tag_base, $head->firstChild);
    }
    else {
      $head->appendChild($tag_base);
    }
  }

  // Update relative URL.
  foreach ($document->getElementsByTagName('a') as $item) {
    $href = $item->getAttribute('href');
    // Keep absolute URL.
    if (parse_url($href, PHP_URL_SCHEME) != '') {
      continue;
    }
    // If href starts with . or ..
    if (substr($href, 0) == '.' || substr($href, 0, 2) == '..' || substr($href, 0) == '/') {
      continue;
    }
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote(MINISITE_EXTENSIONS_NOREWRITE)) . ')$/i';
    if (preg_match($regex, $href)) {
      continue;
    }

    $item->setAttribute('href', '/' . $minisite_node_alias . '/' . $href);
  }

  $html = $document->saveHTML();

  return $html;
}

/**
 * Return minisite paths information.
 */
function minisite_site_paths_info($minisite_info) {
  // Prepare minisite path and alias.
  global $base_url;

  // Load module inc file.
  module_load_include('inc', 'minisite', 'includes/minisite.func');

  $entity_object = entity_load($minisite_info['entity_type'], array($minisite_info['entity_id']));
  $entity_ids = array_keys($entity_object);
  $entity_id = $entity_ids[0];
  $entity = $entity_object[$entity_id];
  $minisite_field_values = minisite_site_field_values($entity);

  $minisite_site_path = $minisite_field_values['site_path'];
  $minisite_node_path = 'node/' . $entity->nid;
  $minisite_node_alias = drupal_get_path_alias($minisite_node_path);

  $minisite_site_url = $base_url . '/' . variable_get('file_public_path', conf_path() . '/files/') . $minisite_site_path . '/';
  $request_path = request_path();
  $request_path = substr(urldecode($request_path), strlen($minisite_node_alias) + 1);
  if (empty($request_path) || $request_path == '/') {
    $request_path = 'index.html';
  }

  $minisite_site_request = 'public://' . $minisite_site_path . '/' . $request_path;

  return array(
    $minisite_site_request,
    $minisite_site_url,
    $minisite_node_alias,
  );
}