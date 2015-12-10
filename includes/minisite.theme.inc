<?php

/**
 * @file
 * minisite.theme.inc
 */

/**
 * Implements hook_theme().
 */
function minisite_theme($existing, $type, $theme, $path) {
  return array(
    'minisite_widget' => array(
      'render element' => 'element',
    ),
  );
}

/**
 * Implements hook_preprocess_html().
 */
function minisite_preprocess_html(&$variables) {
  // Load module inc file.
  module_load_include('inc', 'minisite', 'includes/minisite.func');

  // Get entity and view modes.
  list($entity, $view_mode) = minisite_site_load();

  if (isset($variables['page']['content']['system_main']['nodes'])) {
    $view_mode = $variables['page']['content']['system_main']['nodes'][$entity->nid]['#view_mode'];
  }

  // Provides minisite theme template suggestions.
  if (!empty($entity) && minisite_site_verify($entity) && $view_mode == 'full') {
    if (!minisite_site_alias_verify($entity)) {
      $minisite_field_values = minisite_site_field_values($entity);
      $minisite_site_path = $minisite_field_values['site_path'];
      global $base_url;
      $minisite_site_url = $base_url . '/' . variable_get('file_public_path', conf_path() . '/files/') . $minisite_site_path . '/index.html';
      drupal_goto($minisite_site_url);
    }
    else {
      $variables['theme_hook_suggestions'][] = 'html__minisite';
      $variables['theme_hook_suggestions'][] = 'html__minisite__' . $entity->type;
      $variables['minisite'] = minisite_site_build($entity);
    }
  }
}

/**
 * Implements hook_theme_registry_alter().
 */
function minisite_theme_registry_alter(&$theme_registry) {
  if (isset($theme_registry['html'])) {
    $module_path = drupal_get_path('module', 'minisite');
    $template_file_objects = drupal_find_theme_templates($theme_registry, '.tpl.php', $module_path);
    foreach ($template_file_objects as $key => $template_file_object) {
      // If the template has not already been overridden by a theme.
      if (!isset($theme_registry[$key]['theme path']) || !preg_match('#/themes/#', $theme_registry[$key]['theme path'])) {
        // Alter the theme path and template elements.
        $theme_registry[$key]['theme path'] = $module_path;
        $theme_registry[$key] = array_merge($theme_registry[$key], $template_file_object);
        $theme_registry[$key]['type'] = 'module';
      }
    }
  }
}
