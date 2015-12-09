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
    '#element_validate' => array('_minisite_validate_file_settings_extensions'),
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
      $file = file_load($item['fid']);
      list($archive, $minisite_extract_path, $minisite_base_path) = minisite_site_path($file);

      $archive->extract('public://' . $minisite_extract_path);
      $item['basepath'] = $minisite_base_path;
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

  foreach (element_children($elements) as $delta) {
    // Minisite content extensions validation.
    $minisite_extensions = $settings['minisite_extensions'];
    $elements[$delta]['#upload_validators']['minisite_validate_extensions'] = array($minisite_extensions);
  }

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

/**
 * @param \stdClass $file
 * @param $extensions
 * @return array
 */
function minisite_validate_extensions(stdClass $file, $extensions) {
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
 *
 * @see _file_generic_settings_extensions()
 */
function _minisite_validate_file_settings_extensions($element, &$form_state) {
  if (!empty($element['#value'])) {
    $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
    $extensions = array_filter(explode(' ', $extensions));
    $extensionsArray = array_unique($extensions);
    $extensions = implode(' ', array_unique($extensionsArray));
    if (!preg_match('/^([a-z0-9]+([.][a-z0-9])* ?)+$/', $extensions)) {
      form_error($element, t('The list of allowed extensions is not valid, be sure to exclude leading dots and to separate extensions with a comma or space.'));
    }
    elseif (_minisite_validate_file_blacklist($extensionsArray) === FALSE) {
      form_error($element, t('The list of allowed extensions is not valid. The following extensions are NOT allowed: %extensions-blacklist', array(
        '%extensions-blacklist' => implode(' ', minisite_extensions_blacklist()),
      )));
    }
    else {
      form_set_value($element, $extensions, $form_state);
    }
  }
}

/**
 * Validate minisite file extension in blacklist.
 * @param array $extensions
 */
function _minisite_validate_file_blacklist(Array $extensions) {
  $extensions_blacklist = minisite_extensions_blacklist();
  foreach ($extensions_blacklist as $ext) {
    if (in_array($ext, $extensions)) {
      return FALSE;
    }
  }
}
