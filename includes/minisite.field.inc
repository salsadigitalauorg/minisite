<?php

/**
 * @file
 * Minisite field.
 */

/**
 * Implements hook_field_info().
 */
function minisite_field_info() {
  return array(
    'minisite' => array(
      'label' => t('Minisite asset'),
      'description' => t('Store a reference to a minisite asset.'),
      'settings' => array(
        'uri_scheme' => variable_get('file_default_scheme', 'public'),
      ),
      'instance_settings' => array(
        'file_extensions' => 'zip',
        'minisite_extensions' => MINISITE_EXTENSIONS_WHITELIST,
        'file_directory' => MINISITE_UPLOADPATH,
        'max_filesize' => MINISITE_MAXFILESIZE,
      ),
      'default_widget' => 'file_minisite',
      'default_formatter' => 'file_default',
      // Support hook_entity_property_info() from contrib "Entity API".
      'property_type' => 'field_item_file',
      'property_callbacks' => array('entity_metadata_field_file_callback'),
    ),
  );
}

/**
 * Implements hook_form_field_ui_field_edit_form_alter().
 */
function minisite_form_field_ui_field_edit_form_alter(&$form, &$form_state, $form_id) {
  if ($form['#field']['type'] == 'minisite') {
    $form['field']['cardinality']['#default_value'] = 1;
    $form['field']['cardinality']['#access'] = FALSE;
  }
}

/**
 * Implements hook_field_instance_settings_form().
 */
function minisite_field_instance_settings_form($field, $instance) {
  $settings = $instance['settings'];

  $form['max_filesize'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum upload size'),
    '#default_value' => $settings['max_filesize'],
    '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', array('%limit' => format_size(file_upload_max_size()))),
    '#size' => 10,
    '#element_validate' => array('_file_generic_settings_max_filesize'),
    '#weight' => 5,
  );

  // Make the extension list a little more human-friendly by comma-separation.
  $extensions = str_replace(' ', ', ', $settings['minisite_extensions']);
  $form['minisite_extensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Allowed file extensions in uploaded minisite files'),
    '#default_value' => $extensions,
    '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
    '#element_validate' => array('_minisite_field_validate_minisite_file_settings'),
    // By making this field required, we prevent a potential security issue
    // that would allow files of any type to be uploaded.
    '#required' => TRUE,
    '#maxlength' => 255,
    '#weight' => 11,
  );

  return $form;
}

/**
 * Implements hook_field_settings_form().
 */
function minisite_field_settings_form() {
  return array();
}

/**
 * Implements hook_field_load().
 */
function minisite_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {
  file_field_load($entity_type, $entities, $field, $instances, $langcode, $items, $age);
}

/**
 * Implements hook_field_presave().
 */
function minisite_field_presave($entity_type, $entity, $field, $instance, $langcode, &$items) {
  file_field_presave($entity_type, $entity, $field, $instance, $langcode, $items);

  // Save and extract minisite.
  minisite_site_save($entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_insert().
 */
function minisite_field_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

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
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // Delete all file usages within this entity.
  foreach ($items as $delta => $item) {
    minisite_file_field_delete_file($item, $field, $entity_type, $id, 0);
  }
}

/**
 * Implements hook_field_delete_revision().
 */
function minisite_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
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
 * Implements hook_field_widget_info().
 */
function minisite_field_widget_info() {
  return array(
    'file_minisite' => array(
      'label' => 'Minisite generic',
      'field types' => array('minisite'),
      'settings' => array(
        'progress_indicator' => 'throbber',
      ),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_DEFAULT,
        'default value' => FIELD_BEHAVIOR_DEFAULT,
      ),
    ),
  );
}

/**
 * Implements hook_field_widget_form().
 */
function minisite_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  // Add display_field setting to field
  // because file_field_widget_form() assumes it is set.
  $field['settings']['display_field'] = 0;

  $elements = file_field_widget_form($form, $form_state, $field, $instance, $langcode, $items, $delta, $element);
  $settings = $instance['settings'];

  foreach (element_children($elements) as $delta) {
    // Minisite content extensions validation.
    $minisite_extensions = $settings['minisite_extensions'];
    $elements[$delta]['#upload_validators']['_minisite_field_validate_minisite_asset'] = array($minisite_extensions);

    // Add all extra functionality provided by the minisite widget.
    $elements[$delta]['#process'][] = 'minisite_field_widget_process';
  }

  $elements[0]['#description'] = theme('file_upload_help', array(
    'description' => field_filter_xss($instance['description']),
    'upload_validators' => $elements[0]['#upload_validators'],
  ));

  return $elements[0];
}

/**
 * An element #process callback for the minisite field type.
 */
function minisite_field_widget_process($element, &$form_state, $form) {
  $item = $element['#value'];
  $item['fid'] = $element['fid']['#value'];

  $instance = field_widget_instance($element, $form_state);

  $settings = $instance['settings'];
  $widget_settings = $instance['widget']['settings'];

  $element['#theme'] = 'minisite_widget';

  // Specific settings in serialized data.
  $data = isset($item['data']) ? unserialize($item['data']) : array();

  // Allow minisite use page URL.
  $element['alias_status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Minisite alias'),
    '#default_value' => isset($data['minisite_alias_status']) ? $data['minisite_alias_status'] : '',
    '#description' => t('Allow Minisite use current page URL alias as minisite URL path.'),
    '#weight' => -1,
    '#access' => (bool) $item['fid'],
  );

  return $element;
}

/**
 * Returns HTML for a minisite field widget.
 */
function theme_minisite_widget($variables) {
  $element = $variables['element'];
  $output = '';
  $output .= '<div class="minisite-widget form-managed-file clearfix">';
  $output .= '<div class="minisite-widget-data">';
  if ($element['fid']['#value'] != 0) {
    $element['filename']['#markup'] .= ' <span class="file-size">(' . format_size($element['#file']->filesize) . ')</span> ';
  }
  $output .= drupal_render_children($element);
  $output .= '</div>';
  $output .= '</div>';

  return $output;
}

/**
 * Implements hook_field_formatter_info_alter().
 */
function minisite_field_formatter_info_alter(&$info) {
  $info['file_default']['field types'][] = 'file_minisite';
  $info['file_table']['field types'][] = 'file_minisite';
  $info['file_url_plain']['field types'][] = 'file_minisite';
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
