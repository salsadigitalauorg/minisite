<?php

/**
 * @file
 * minisite.site.inc
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Load minisite.
 */
function minisite_site_load() {
  $minisite_info = &drupal_static(__FUNCTION__);

  if (!isset($minisite_info)) {
    // Load module inc file.
    module_load_include('inc', 'minisite', 'includes/minisite.site');
    $minisite_info = minisite_site_lookup_path();
  }

  if (!isset($minisite_info['mid'])) {
    return FALSE;
  }

  if (isset($minisite_info['alias_status']) && $minisite_info['alias_status'] == 1) {
    return $minisite_info;
  }
  else {
    $minisite_site_url = _minisite_site_file_public_path_base_url() . $minisite_info['source'] . '/index.html';
    drupal_goto($minisite_site_url);
  }
}

/**
 * Save and extract minisite.
 */
function minisite_site_save(EntityInterface $entity) {
  dpm($entity, 'minisite');
  return;
  foreach ($items as &$item) {
    if (isset($item['fid'])) {
      $file = file_load($item['fid']);
      if (!empty($file)) {
        // Load module inc file.
        module_load_include('inc', 'minisite', 'includes/minisite.site');
        list($minisite_extract_path, $minisite_base_path, $minisite_listing) = minisite_site_asset_info($item['fid'], FALSE);

        // Extract files.
        minisite_site_extract($file, $minisite_extract_path);

        // Save specific settings in data.
        $item['site_path'] = $minisite_extract_path . '/' . $minisite_base_path;
        $minisite_field_data = array(
          'minisite_alias_status' => $item['alias_status'],
          'minisite_extract_path' => $minisite_extract_path,
          'minisite_base_path' => $minisite_base_path,
        );
        $item['data'] = serialize($minisite_field_data);

        $minisite_info = array(
          'minisite_extract_path' => $minisite_extract_path,
          'minisite_base_path' => $minisite_base_path,
          'minisite_listing' => $minisite_listing,
        );

        // Save assets information.
        drupal_register_shutdown_function('minisite_site_save_asset', $entity_type, $entity, $field['field_name'], $langcode, $item, $minisite_info);
      }
    }
  }
}

/**
 * Save minisite asset into database.
 */
function minisite_site_save_asset($entity_type, $entity, $field_name, $langcode, $item, $minisite_info) {
  $entity_uri = entity_uri($entity_type, $entity);
  $entity_path = $entity_uri['path'];
  $entity_path_alias = drupal_get_path_alias($entity_path, $langcode);
  list($entity_id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
  // Prepare keys.
  $keys = array(
    'entity_type' => $entity_type,
    'bundle' => $bundle,
    'entity_id' => $entity_id,
    'revision_id' => $vid,
    'minisite_field_name' => $field_name,
    'minisite_fid' => $item['fid'],
    'language' => $langcode,
    'source' => $minisite_info['minisite_extract_path'] . '/' . $minisite_info['minisite_base_path'],
  );
  // Prepare fields.
  $fields = array(
    'alias' => $entity_path_alias,
    'alias_status' => $item['alias_status'],
  );

  // Save minisite landing page information.
  db_merge('minisite_asset')
    ->key($keys)
    ->fields($fields)
    ->execute();

  // Save minisite assets information.
  if (isset($item['alias_status']) && $item['alias_status'] == 1) {
    foreach ($minisite_info['minisite_listing'] as $minisite_file) {
      if (strpos(strtolower($minisite_file), '.html') !== FALSE || strpos(strtolower($minisite_file), '.htm') !== FALSE) {
        $keys['source'] = $minisite_info['minisite_extract_path'] . '/' . $minisite_file;
        $search = $minisite_info['minisite_base_path'];
        $replace = $entity_path_alias;
        $fields['alias'] = str_replace($search, $replace, $minisite_file);
        db_merge('minisite_asset')
          ->key($keys)
          ->fields($fields)
          ->execute();
      }
    }
  }
}

/**
 * Delete minisite.
 */
function minisite_site_delete($item, $field, $entity_type, $id) {
  $file = (object) $item;
  $file_usage = file_usage_list($file);
  if ($file->status == 0 || !empty($file_usage['minisite'])) {
    if (!empty($file)) {
      // Delete database records.
      db_delete('minisite_asset')
        ->condition('minisite_fid', $file->fid)
        ->execute();
      // Load module inc file.
      module_load_include('inc', 'minisite', 'includes/minisite.site');
      $path = 'public://' . MINISITE_ASSETPATH . '/' . minisite_site_hash($file);
      file_unmanaged_delete_recursive($path);
    }
  }
}

/**
 * Build minisite.
 */
function minisite_site_build($minisite_info) {
  $minisite_html = &drupal_static(__FUNCTION__);

  if (!isset($minisite_html)) {
    $minisite_html = array();
  }

  if (!isset($minisite_html[$minisite_info['mid']])) {
    // Load module inc file.
    module_load_include('inc', 'minisite', 'includes/minisite.site');
    $html = minisite_site_parse($minisite_info);
    if (empty($html)) {
      $minisite_html[$minisite_info['mid']] = '';
      _minisite_fast_404();
    }
    else {
      $minisite_html[$minisite_info['mid']] = $html;
    }
  }

  return $minisite_html[$minisite_info['mid']];
}

/**
 * Given an alias, return its minisite information if one exists.
 */
function minisite_site_lookup_path($path = '', $path_language = NULL) {
  global $language_url;
  // Use the advanced drupal_static() pattern.
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
    }
    else {
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

  // Generate minisite full alias.
  $minisite_full_alias = url($minisite_entity_alias, array('absolute' => TRUE));

  // Update link tag.
  foreach ($document->getElementsByTagName('a') as $item) {
    $href = $item->getAttribute('href');
    // Keep absolute URL.
    if (parse_url($href, PHP_URL_SCHEME) != '') {
      continue;
    }
    // If href starts with . or root link.
    if (substr($href, 0) == '.' || substr($href, 0) == '/') {
      continue;
    }
    // If href starts with relative path.
    if (substr($href, 0, 2) == '..') {
      $item->setAttribute('href', $minisite_full_alias . '/' . substr($href, 3));
      continue;
    }
    // If href is marked not rewrite, then ignore it.
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote(MINISITE_EXTENSIONS_NOREWRITE)) . ')$/i';
    if (preg_match($regex, $href)) {
      continue;
    }

    // Default URL rewrite behaviours.
    $regex = "/" . preg_quote($minisite_entity_alias, '/') . "\/(.*)\/" . basename(current_path()) . "/";
    $relative = '';
    preg_match($regex, current_path(), $matches);
    if (!empty($matches[1])) {
      $relative = $matches[1] . '/';
    }
    $item->setAttribute('href', $minisite_full_alias . '/' . $relative . $href);
  }

  // Replace link.
  foreach ($document->getElementsByTagName('link') as $item) {
    $href = $item->getAttribute('href');
    // Relative path.
    if (substr($href, 0, 2) == '..') {
      $item->setAttribute('href', $minisite_site_url . substr($href, 3));
      continue;
    }
  }

  // Replace script.
  foreach ($document->getElementsByTagName('script') as $item) {
    $src = $item->getAttribute('src');
    // Relative path.
    if (substr($href, 0, 2) == '..') {
      $item->setAttribute('src', $minisite_site_url . substr($src, 3));
      continue;
    }
  }

  // Replace image tag.
  foreach ($document->getElementsByTagName('img') as $item) {
    $src = $item->getAttribute('src');
    // Relative path.
    if (substr($href, 0, 2) == '..') {
      $item->setAttribute('src', $minisite_site_url . substr($src, 3));
      continue;
    }
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
    $minisite_site_base_url_real = $base_url . '/' . variable_get('file_public_path', conf_path() . '/files') . '/';
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
