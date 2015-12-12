<?php

/**
 * @file
 * minisite.func.inc
 */

/**
 * Check minisite entity status.
 */
function minisite_site_verify($entity) {
  if (empty($entity) && empty($entity->nid)) {
    return FALSE;
  }

  if (!minisite_site_bundle_verify($entity->type)) {
    return FALSE;
  }

  return TRUE;
}

/**
 * If current minisite is allowed to use page alias.
 */
function minisite_site_alias_verify($entity) {
  if (!$entity) {
    return FALSE;
  }

  $minisite_field_values = minisite_site_field_values($entity);

  $minisite_site_data = unserialize($minisite_field_values['data']);
  if (isset($minisite_site_data['alias']) && $minisite_site_data['alias'] == 1) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
 * Check minisite bundle.
 */
function minisite_site_bundle_verify($bundle) {
  $bundles_tree = minisite_site_bundles_info();
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
function minisite_site_bundles_info() {
  $bundles = &drupal_static(__FUNCTION__);

  if (!isset($bundles)) {
    $bundles = array();
    $minisite_fields = minisite_site_fields_info('minisite', 'node');
    foreach ($minisite_fields as $minisite_field) {
      foreach ($minisite_field['bundles']['node'] as $bundle) {
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
 */
function minisite_site_fields_info($field_type, $entity_type = NULL) {
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
 * Return minisite field name by given bundle name.
 */
function minisite_site_field_with_bundle($bundle) {
  $bundles_tree = minisite_site_bundles_info();
  $bundles = array_keys($bundles_tree);
  if (in_array($bundle, $bundles)) {
    return $bundles_tree[$bundle];
  }
  else {
    return FALSE;
  }
}

/**
 * Return minisite site field values.
 */
function minisite_site_field_values($entity) {
  $minisite_field_values = NULL;
  // Get field name.
  $minisite_field_name = minisite_site_field_with_bundle($entity->type);

  if (module_exists('entity')) {
    $wrapper = entity_metadata_wrapper('node', $entity);
    $minisite_field_values = $wrapper->$minisite_field_name->value();
  }
  else {
    $minisite_field = $entity->$minisite_field_name;
    $minisite_field_values = $minisite_field[LANGUAGE_NONE][0];
  }

  return $minisite_field_values;
}
