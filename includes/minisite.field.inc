<?php

/**
 * @file
 * Minisite field.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function minisite_form_field_storage_config_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form['#title'] == 'Minisite') {
    $form['cardinality_container']['#access'] = FALSE;
    $form['cardinality_container']['cardinality_number']['#default_value'] = 1;
    $form['cardinality_container']['cardinality_number']['#access'] = FALSE;
  }
}

/**
 * Implements hook_field_insert().
 */
function minisite_field_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, ,) = entity_extract_ids($entity_type, $entity);

  // Add a new usage of each uploaded file.
  foreach ($items as $item) {
    $file = (object) $item;
    file_usage_add($file, 'minisite', $entity_type, $id);
  }
}

/**
 * Implements hook_field_update().
 */
function minisite_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // Check whether the field is defined on the object.
  if (!isset($entity->{$field['field_name']})) {
    // We cannot check for removed files if the field is not defined.
    return;
  }

  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // On new revisions, all files are considered to be a new usage and no
  // deletion of previous file usages are necessary.
  if (!empty($entity->revision)) {
    foreach ($items as $item) {
      $file = (object) $item;
      file_usage_add($file, 'minisite', $entity_type, $id);
    }

    return;
  }

  // Build a display of the current FIDs.
  $current_fids = array();
  foreach ($items as $item) {
    $current_fids[] = $item['fid'];
  }

  // Compare the original field values with the ones that are being saved. Use
  // $entity->original to check this when possible, but if it isn't available,
  // create a bare-bones entity and load its previous values instead.
  if (isset($entity->original)) {
    $original = $entity->original;
  }
  else {
    $original = entity_create_stub_entity($entity_type, array(
      $id,
      $vid,
      $bundle,
    ));
    field_attach_load($entity_type, array($id => $original), FIELD_LOAD_CURRENT, array('field_id' => $field['id']));
  }
  $original_fids = array();
  if (!empty($original->{$field['field_name']}[$langcode])) {
    foreach ($original->{$field['field_name']}[$langcode] as $original_item) {
      $original_fids[] = $original_item['fid'];
      if (isset($original_item['fid']) && !in_array($original_item['fid'], $current_fids)) {
        // Decrement the file usage count by 1 and delete the file if possible.
        minisite_file_field_delete_file($original_item, $field, $entity_type, $id);
      }
    }
  }

  // Add new usage entries for newly added files.
  foreach ($items as $item) {
    if (!in_array($item['fid'], $original_fids)) {
      $file = (object) $item;
      file_usage_add($file, 'minisite', $entity_type, $id);
    }
  }
}

/**
 * Implements hook_field_delete().
 */
function minisite_field_delete($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, ,) = entity_extract_ids($entity_type, $entity);

  // Delete all file usages within this entity.
  foreach ($items as $item) {
    minisite_file_field_delete_file($item, $field, $entity_type, $id, 0);
  }
}

/**
 * Implements hook_field_delete_revision().
 */
function minisite_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, ,) = entity_extract_ids($entity_type, $entity);
  foreach ($items as $delta => $item) {
    // Decrement the file usage count by 1 and delete the file if possible.
    if (minisite_file_field_delete_file($item, $field, $entity_type, $id)) {
      $items[$delta] = NULL;
    }
  }
}

/**
 * Delete minisite file field data.
 */
function minisite_file_field_delete_file($item, $field, $entity_type, $id, $count = 1) {
  // To prevent the file field from deleting files it doesn't know about, check
  // the file reference count. Temporary files can be deleted because they
  // are not yet associated with any content at all.
  $file = (object) $item;
  $file_usage = file_usage_list($file);

  // Delete minisite.
  minisite_site_delete($item, $field, $entity_type, $id);

  if ($file->status == 0 || !empty($file_usage['minisite'])) {
    file_usage_delete($file, 'minisite', $entity_type, $id, $count);

    return file_delete($file);
  }

  // Even if the file is not deleted, return TRUE to indicate the file field
  // record can be removed from the field database tables.
  return TRUE;
}

/**
 * Implements hook_field_is_empty().
 */
function minisite_field_is_empty($item, $field) {
  return file_field_is_empty($item, $field);
}

/**
 * Validate minisite asset.
 */
function _minisite_field_validate_minisite_asset(stdClass $file, $extensions) {
  $errors = array();

  // Validate minisite assets.
  $archive = MinisiteArchive::open($file);
  if (!$archive) {
    $errors[] = t('Unable to open minisite archive file.');

    return $errors;
  }

  $tree = $archive->filesTree();
  // Ignore __MACOSX folder.
  unset($tree['__MACOSX']);

  // Limit directory structure in minisite.
  $root_files = array_keys($tree);
  if (count($root_files) !== 1 || !is_array($tree[$root_files[0]])) {
    $errors[] = t('Minisite must have a single top level directory.');

    return $errors;
  }

  $top_folder = $root_files[0];
  $top_level = $tree[$top_folder];
  if (!array_key_exists('index.html', $top_level)) {
    $errors[] = t('Minisite must contain a index.html file.');
  }

  $files = $archive->filesList();

  // Check minisite asset files extensions.
  $invalid_files = MinisiteArchive::fileExtensionCheck($files, $extensions);
  if (!empty($invalid_files)) {
    foreach ($invalid_files as $invalid_file) {
      $errors[] = t('Minisite contains files with invalid extensions: %files. Only files with the following extensions are allowed: %allowed-extensions', array(
        '%allowed-extensions' => $extensions,
        '%files' => $invalid_file,
      ));
    }
  }

  return $errors;
}

/**
 * Element validate callback for the allowed file extensions field.
 */
function _minisite_field_validate_minisite_file_settings($element, &$form_state) {
  if (!empty($element['#value'])) {
    $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
    $extensions = array_filter(explode(' ', $extensions));
    $extensions_array = array_unique($extensions);
    $extensions = implode(' ', array_unique($extensions_array));
    if (!preg_match('/^([a-z0-9]+([.][a-z0-9])* ?)+$/', $extensions)) {
      form_error($element, t('The list of allowed extensions is not valid, be sure to exclude leading dots and to separate extensions with a comma or space.'));
    }
    elseif (_minisite_field_validate_minisite_file_blacklist($extensions_array) === FALSE) {
      form_error($element, t('The list of allowed extensions is not valid. The following extensions are NOT allowed: %extensions-blacklist', array(
        '%extensions-blacklist' => implode(' ', minisite_site_extensions_blacklist()),
      )));
    }
    else {
      form_set_value($element, $extensions, $form_state);
    }
  }
}

/**
 * Validate minisite file extension in blacklist.
 */
function _minisite_field_validate_minisite_file_blacklist(array $extensions) {
  // Load module inc file.
  module_load_include('inc', 'minisite', 'includes/minisite.site');

  $extensions_blacklist = minisite_site_extensions_blacklist();
  foreach ($extensions_blacklist as $ext) {
    if (in_array($ext, $extensions)) {
      return FALSE;
    }
  }
}
