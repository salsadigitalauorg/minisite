<?php

/**
 * @file
 * minisite.func.inc
 */

/**
 * Check minisite entity status.
 * @param $entity
 * @return bool
 */
function minisite_entity_check($entity) {
  if (empty($entity) && empty($entity->nid)) {
    return FALSE;
  }

  if (!minisite_entity_bundle_check($entity->type)) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Check minisite bundle.
 * @param $bundle
 * @return bool
 */
function minisite_entity_bundle_check($bundle) {
  $bundles_tree = minisite_info_bundles();
  $bundles = array_keys($bundles_tree);
  if (in_array($bundle, $bundles)) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
 * Return minisite information with bundled entities.
 */
function minisite_info_bundles() {
  $bundles =  &drupal_static(__FUNCTION__);

  if (!isset($bundles)) {
    $bundles = array();
    $minisite_fields = minisite_info_fields('minisite', 'node');
    foreach ($minisite_fields as $minisite_field) {
      foreach ($minisite_field['bundles']['node'] as $bundle) {
        // $field_instance = field_info_instance('node', $minisite_field['field_name'], $bundle);
        // $field_instance_settings = $field_instance['settings'];
        if (!empty($bundle)) {
          $bundles[$bundle] = $minisite_field['field_name'];
        }
      }
    }
  }

  return $bundles;
}

/**
 * Finds all fields of a particular field type.
 * @param $field_type
 * @param $entity_type
 * @return array
 */
function minisite_info_fields($field_type, $entity_type = NULL) {
  $fields = array();

  // Loop through the fields looking for any fields of the specified type.
  foreach (field_info_field_map() as $field_name => $field_stub) {
    if ($field_stub['type'] == $field_type) {
      // Add this field to the return array if no entity type was specified or
      // if the specified type exists in the field's bundles array.
      if (empty($entity_type) || in_array($entity_type, array_keys($field_stub['bundles']))) {
        $field = field_info_field($field_name);
        $fields[$field_name] = $field;
      }
    }
  }

  return $fields;
}

/**
 * Load current entity/node.
 * @return array
 */
function minisite_load_entity() {
  $view_mode = FALSE;

  $path = current_path();
  if (strpos($path, 'node/') === FALSE) {
    $segments = explode('/', $path);
    foreach ($segments as $k => $segment) {
      $internal = drupal_get_normal_path($segment);
      if (strpos($internal, 'node/') !== FALSE) {
        $path = $internal;
        $view_mode = 'full';
        break;
      }
    }
  }

  $entity = menu_get_object('node', 1, $path);
  return array($entity, $view_mode);
}

/**
 * Return minisite field name by given bundle name.
 * @param $bundle
 * @return bool
 */
function minisite_field_by_bundle($bundle) {
  $bundles_tree = minisite_info_bundles();
  $bundles = array_keys($bundles_tree);
  if (in_array($bundle, $bundles)) {
    return $bundles_tree[$bundle];
  }
  else {
    return FALSE;
  }
}

/**
 * Build minisite raw html.
 * @param $entity
 * @return string
 */
function minisite_build_html($entity) {
  $minisite_field_name = minisite_field_by_bundle($entity->type);
  $wrapper = entity_metadata_wrapper('node', $entity);
  $minisite_field_values = $wrapper->$minisite_field_name->value();

  // Prepare minisite path and alias.
  global $base_url;
  $minisite_basepath =  $minisite_field_values['basepath'];
  $minisite_nodepath = 'node/' . $entity->nid;
  $minisite_pathalias = drupal_get_path_alias($minisite_nodepath);
  $minisite_realurl = $base_url . '/' . variable_get('file_public_path', conf_path() . '/files/') . $minisite_basepath . '/';
  $request_path = request_path();
  $request_path = substr(urldecode($request_path), strlen($minisite_pathalias) + 1);
  if (empty($request_path) || $request_path == '/') {
    $request_path = 'index.html';
  }

  // Bypass lib error.
  libxml_use_internal_errors(TRUE);

  $resource = 'public://' . $minisite_basepath . '/' . $request_path;
  $document = new DOMDocument;
  $document->loadHTMLFile($resource);

  // Update base href.
  $base = $document->createElement('base');
  $base->setAttribute('href', $minisite_realurl);

  $head = $document->getElementsByTagName('head')->item(0);
  if ($head->hasChildNodes()) {
    $head->insertBefore($base, $head->firstChild);
  }
  else {
    $head->appendChild($base);
  }

  // Update relative URL.
  foreach ($document->getElementsByTagName('a') as $item) {
    $href = $item->getAttribute('href');
    // Keep absolute URL.
    if (parse_url($href, PHP_URL_SCHEME) != '') {
      continue;
    }
    $item->setAttribute('href', '/' . $minisite_pathalias . '/' . $href);
  }

  $html = $document->saveHTML();
  return $html;
}
