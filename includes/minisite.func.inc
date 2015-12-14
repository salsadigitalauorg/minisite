<?php

/**
 * @file
 * minisite.func.inc
 */

/**
 * Return minisite asset field values.
 */
function minisite_asset_field_values($entity_type, $entity, $minisite_asset_field_name = NULL) {
  // Try to get minisite field name if NULL is given.
  if (empty($minisite_asset_field_name)) {
    $minisite_asset_field_name = minisite_asset_field_name($entity_type, $entity);
  }

  if (module_exists('entity')) {
    $wrapper = entity_metadata_wrapper($entity_type, $entity);
    $minisite_asset_field_values = $wrapper->$minisite_asset_field_name->value();
  }
  else {
    $minisite_field = $entity->$minisite_asset_field_name;
    $minisite_asset_field_values = $minisite_field[LANGUAGE_NONE][0];
  }

  return $minisite_asset_field_values;
}

/**
 * Return minisite asset field name.
 */
function minisite_asset_field_name($entity_type, $entity) {
  // Get field name.
  $minisite_asset_field_name = minisite_asset_field_info($entity_type, $entity);

  return $minisite_asset_field_name;
}

/**
 * Return minisite field name by given bundle name.
 */
function minisite_asset_field_info($entity_type, $entity) {
  $minisite_bundles_tree = minisite_asset_field_bundles_info($entity_type);
  $minisite_bundles = array_keys($minisite_bundles_tree[$entity_type]);
  list($entity_id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
  if (in_array($bundle, $minisite_bundles)) {
    return $minisite_bundles_tree[$bundle];
  }
  else {
    return FALSE;
  }
}

/**
 * Return minisite information with bundled entities.
 */
function minisite_asset_field_bundles_info($entity_type = 'node') {
  $minisite_bundles = &drupal_static(__FUNCTION__);

  if (!isset($minisite_bundles)) {
    $minisite_bundles = array();
  }

  if (!isset($minisite_bundles[$entity_type])) {
    $minisite_fields = minisite_asset_fields_info('minisite', $entity_type);
    foreach ($minisite_fields as $minisite_field) {
      foreach ($minisite_field['bundles'][$entity_type] as $bundle) {
        if (!empty($bundle)) {
          $minisite_bundles[$entity_type][$bundle] = $minisite_field['field_name'];
        }
      }
    }
  }

  return $minisite_bundles;
}

/**
 * Finds all fields of a particular field type.
 */
function minisite_asset_fields_info($field_type, $entity_type = NULL) {
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
