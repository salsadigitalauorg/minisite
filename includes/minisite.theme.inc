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

/**
 * Alter page delivery callback.
 */
function minisite_page_delivery_callback_alter(&$callback) {
  // Get entity and view modes.
  $minisite_info = minisite_site_load();

  if (!empty($minisite_info) && $callback == 'drupal_deliver_html_page') {
    $callback = 'minisite_deliver';
  }
}

/**
 * Deliver minisite.
 */
function minisite_deliver($page_callback_result) {
  if (isset($page_callback_result) && is_null(drupal_get_http_header('Content-Type'))) {
    drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
  }

  // Send appropriate HTTP-Header for browsers and search engines.
  global $language;
  drupal_add_http_header('Content-Language', $language->language);

  $minisite_info = minisite_site_load();
  $content = minisite_site_build($minisite_info);
  print $content;

  // Perform end-of-request tasks.
  drupal_page_footer();
}
