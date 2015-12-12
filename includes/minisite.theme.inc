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
  // Get entity and view modes.
  $minisite_info = minisite_site_load();

  // Provides minisite theme template suggestions.
  if (!empty($minisite_info)) {
    $variables['theme_hook_suggestions'][] = 'html__minisite';
    $variables['theme_hook_suggestions'][] = 'html__minisite__' . $minisite_info['entity_type'];
    $variables['theme_hook_suggestions'][] = 'html__minisite__' . $minisite_info['entity_type'] . '__' . $minisite_info['bundle'];
    $variables['minisite'] = minisite_site_build($minisite_info);
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
