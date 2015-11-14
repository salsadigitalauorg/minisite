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
        'minisite_extensions' => MINISITE_EXTENSIONS,
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
    '#element_validate' => array('_file_generic_settings_extensions'),
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

  foreach ($items as &$item) {
    if (isset($item['fid'])) {
      $asset = file_load($item['fid']);
      $hash = drupal_hmac_base64($asset->filename, MINISITE_HASHSALT);
      $file_path = drupal_realpath($asset->uri);
      $zip = new ArchiverZip($file_path);
      $zip->extract('public://' . MINISITE_ASSETPATH . '/' . $hash . '/');
      $item['basepath'] = MINISITE_ASSETPATH . '/' . $hash;
    }
  }
}

/**
 * Implements hook_field_insert().
 */
function minisite_field_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  file_field_insert($entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_update().
 */
function minisite_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  file_field_update($entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_delete().
 */
function minisite_field_delete($entity_type, $entity, $field, $instance, $langcode, &$items) {
  file_field_delete($entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_delete_revision().
 */
function minisite_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, &$items) {
  file_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, $items);
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
  // Add display_field setting to field because file_field_widget_form() assumes it is set.
  $field['settings']['display_field'] = 0;

  $elements = file_field_widget_form($form, $form_state, $field, $instance, $langcode, $items, $delta, $element);
  $settings = $instance['settings'];

  $elements[0]['#description'] = theme('file_upload_help', array(
    'description' => field_filter_xss($instance['description']),
    'upload_validators' => $elements[0]['#upload_validators']
  ));

  return $elements[0];
}

/**
 * Implements hook_field_formatter_info_alter().
 */
function minisite_field_formatter_info_alter(&$info) {
  $info['file_default']['field types'][] = 'file_minisite';
  $info['file_table']['field types'][] = 'file_minisite';
  $info['file_url_plain']['field types'][] = 'file_minisite';
}
