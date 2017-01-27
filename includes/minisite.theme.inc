<?php

/**
 * @file
 * minisite.theme.inc
 */

use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\file\Entity\File;
use Drupal\Core\Template\Attribute;

/**
 * Implements hook_theme().
 */
function minisite_theme($existing, $type, $theme, $path) {
  return array(
    'minisite_widget' => array(
      'render element' => 'element',
    ),
    'minisite_link' => array(
      'variables' => array(
        'file' => NULL,
        'description' => NULL,
        'attributes' => array(),
      ),
    ),
  );
}

/**
 * Prepares variables for minisite widget templates.
 *
 * Default template: minisite-widget.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the minisite field widget.
 */
function template_preprocess_minisite_widget(&$variables) {
  $element = $variables['element'];

  $variables['attributes'] = array(
    'class' => array(
      'minisite-widget',
      'js-form-managed-file',
      'form-managed-file',
      'clearfix',
    ),
  );

  if (!empty($element['fids']['#value'])) {
    $file = reset($element['#files']);
    $element['file_' . $file->id()]['filename']['#suffix'] = ' <span class="file-size">(' . format_size($file->getSize()) . ')</span> ';
  }

  $variables['data'] = array();
  foreach (Element::children($element) as $child) {
    $variables['data'][$child] = $element[$child];
  }
}

/**
 * Prepares variables for minisite link templates.
 *
 * Default template: minisite-link.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - file: A file object to which the link will be created.
 *   - icon_directory: (optional) A path to a directory of icons to be used for
 *     files. Defaults to the value of the "icon.directory" variable.
 *   - description: A description to be displayed instead of the filename.
 *   - attributes: An associative array of attributes to be placed in the a tag.
 */
function template_preprocess_minisite_link(&$variables) {
  $file = $variables['file'];
  $options = array();

  $file_entity = ($file instanceof File) ? $file : File::load($file->fid);
  // @todo Wrap in file_url_transform_relative(). This is currently
  // impossible. As a work-around, we currently add the 'url.site' cache context
  // to ensure different file URLs are generated for different sites in a
  // multisite setup, including HTTP and HTTPS versions of the same site.
  // Fix in https://www.drupal.org/node/2646744.
  $url = isset($file->asset_path) ? file_create_url($file->asset_path) : file_create_url($file_entity->getFileUri());
  $variables['#cache']['contexts'][] = 'url.site';

  $mime_type = $file->getMimeType();
  // Set options as per anchor format described at
  // http://microformats.org/wiki/file-format-examples
  $options['attributes']['type'] = $mime_type . '; length=' . $file->getSize();

  // Use the description as the link text if available.
  if (empty($variables['description'])) {
    $link_text = $file_entity->getFilename();
  }
  else {
    $link_text = $variables['description'];
    $options['attributes']['title'] = $file_entity->getFilename();
  }

  // Classes to add to the file field for icons.
  $classes = array(
    'file',
    // Add a specific class for each and every mime type.
    'file--mime-' . strtr($mime_type, array('/' => '-', '.' => '-')),
    // Add a more general class for groups of well known MIME types.
    'file--' . file_icon_class($mime_type),
  );

  // Set file classes to the options array.
  $variables['attributes'] = new Attribute($variables['attributes']);
  $variables['attributes']->addClass($classes);

  $variables['link'] = \Drupal::l($link_text, Url::fromUri($url, $options));
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
