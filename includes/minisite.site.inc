<?php

/**
 * @file
 * minisite.site.inc
 */

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
function minisite_site_lookup_path($path = '', $path_language = NULL) {
  global $language_url;
  // Use the advanced drupal_static() pattern;
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['cache'] = &drupal_static(__FUNCTION__);
  }
  $cache = &$drupal_static_fast['cache'];

  if (!isset($cache)) {
    $cache = array(
      'map' => array(),
    );
  }

  $path = $path ? $path : current_path();
  // If current path has a alias.
  $path = drupal_get_path_alias($path);
  $path_language = $path_language ? $path_language : $language_url->language;

  if ($path != '') {
    // If the alias has already been loaded, return it.
    if (isset($cache['map'][$path_language][$path])) {
      return $cache['map'][$path_language][$path];
    } else {
      $cache['map'][$path_language] = array();
      // Prepare query args.
      $args = array(
        ':source' => $path,
        ':language' => $path_language,
        ':language_none' => LANGUAGE_NONE,
      );
      // Find minisite information.
      if ($path_language == LANGUAGE_NONE) {
        unset($args[':language']);
        $result = db_query("SELECT mid, entity_type, bundle, entity_id, minisite_field_name, minisite_fid, language ,source, alias_status FROM {minisite_asset} WHERE alias = :source AND language = :language_none", $args)->fetchAssoc();
      }
      elseif ($path_language > LANGUAGE_NONE) {
        $result = db_query("SELECT mid, entity_type, bundle, entity_id, minisite_field_name, minisite_fid, language, source, alias_status FROM {minisite_asset} WHERE alias = :source AND language IN (:language, :language_none) ORDER BY language DESC", $args)->fetchAssoc();
      }
      else {
        $result = db_query("SELECT mid, entity_type, bundle, entity_id, minisite_field_name, minisite_fid, language, source, alias_status FROM {minisite_asset} WHERE alias = :source AND language IN (:language, :language_none) ORDER BY language ASC", $args)->fetchAssoc();
      }

      $cache['map'][$path_language][$path] = $result;
      return $result;
    }
  }

  return FALSE;
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
  list($minisite_site_request, $minisite_site_url, $minisite_entity_alias) = minisite_site_paths_info($minisite_info);

  // Make sure a HTML file is called.
  if (substr($minisite_site_request, -5, 1) <> '.') {
    $minisite_site_request = $minisite_site_request . '/index.html';
  }

  // Bypass lib error.
  libxml_use_internal_errors(TRUE);

  $document = new \DOMDocument();
  $document->loadHTMLFile($minisite_site_request);

  // Return 404 if document is empty.
  if (empty($document) || empty($document->textContent)) {
    return FALSE;
  }

  $head = $document->getElementsByTagName('head')->item(0);
  // Return 404 is document head is empty.
  if (empty($head)) {
    return FALSE;
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
    // If href is marked not rewrite, then ignore it.
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote(MINISITE_EXTENSIONS_NOREWRITE)) . ')$/i';
    if (preg_match($regex, $href)) {
      continue;
    }

    $item->setAttribute('href', '/' . $minisite_entity_alias . '/' . $href);
  }

  $html = $document->saveHTML();

  return $html;
}

/**
 * Return minisite paths information.
 */
function minisite_site_paths_info($minisite_info) {
  $minisite_site_request = '';
  $minisite_site_url = '';
  $minisite_entity_alias = '';

  // Load module inc file.
  module_load_include('inc', 'minisite', 'includes/minisite.func');

  // Get entity object.
  $entity = _minisite_site_load_entity($minisite_info['entity_type'], array($minisite_info['entity_id']));

  if ($entity) {
    $minisite_site_request = 'public://' . $minisite_info['source'];
    // Get minisite base href url.
    $minisite_field_values = minisite_asset_field_values($minisite_info['entity_type'], $entity, $minisite_info['minisite_field_name']);
    $minisite_site_path = $minisite_field_values['site_path'];
    $minisite_site_url = _minisite_site_file_public_path_base_url() . $minisite_site_path . '/';
    // Get entity alias path.
    $minisite_entity_alias = _minisite_site_load_alias($minisite_info['entity_type'], $entity);
  }

  return array(
    $minisite_site_request,
    $minisite_site_url,
    $minisite_entity_alias,
  );
}

/**
 * Fetch minisite information.
 */
function minisite_site_asset_info($fid, $tree = TRUE) {
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
 * Return real minisite file public path base url.
 */
function _minisite_site_file_public_path_base_url() {
  $minisite_site_base_url_real = &drupal_static(__FUNCTION__);

  if (!isset($minisite_site_base_url_real)) {
    global $base_url;
    $minisite_site_base_url_real = $base_url . '/' . variable_get('file_public_path', conf_path() . '/files/');
  }

  return $minisite_site_base_url_real;
}

/**
 * Return entity object.
 */
function _minisite_site_load_entity($entity_type, $entity_ids = array()) {
  if (empty($entity_type) || empty($entity_ids)) {
    return array();
  }

  $entity_object = entity_load($entity_type, $entity_ids);
  if (empty($entity_object)) {
    return array();
  }

  $entity_ids = array_keys($entity_object);
  $entity_id = $entity_ids[0];
  return $entity_object[$entity_id];
}

/**
 * Return minisite entity alias path.
 */
function _minisite_site_load_alias($entity_type, $entity) {
  $entity_uri = entity_uri($entity_type, $entity);
  $entity_path = $entity_uri['path'];
  return drupal_get_path_alias($entity_path);
}

/**
 * Return a fast 404 page.
 */
function _minisite_fast_404() {
  drupal_add_http_header('Status', '404 Not Found');
  $fast_404_html = variable_get('404_fast_html', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>');
  // Replace @path in the variable with the page path.
  print strtr($fast_404_html, array('@path' => check_plain(request_uri())));
  exit;
}
