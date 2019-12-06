<?php

/**
 * @file
 * minisite.theme.inc
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\minisite\LegacyWrapper;

/**
 * Implements hook_theme().
 */
function minisite_theme($existing, $type, $theme, $path) {
  return [
    'minisite_widget' => [
      'render element' => 'element',
    ],
    'minisite_link' => [
      'variables' => [
        'file' => NULL,
        'asset_path' => NULL,
        'description' => NULL,
        'attributes' => [],
      ],
    ],
  ];
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
function template_preprocess_minisite_widget(array &$variables) {
  $element = $variables['element'];

  $variables['attributes'] = [
    'class' => [
      'minisite-widget',
      'js-form-managed-file',
      'form-managed-file',
      'clearfix',
    ],
  ];

  if (!empty($element['fids']['#value'])) {
    $file = reset($element['#files']);
    $element['file_' . $file->id()]['filename']['#suffix'] = ' <span class="file-size">(' . format_size($file->getSize()) . ')</span> ';
  }

  $variables['data'] = [];
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
 *   - asset_path: Path to the first asset in the minisite.
 *   - icon_directory: (optional) A path to a directory of icons to be used for
 *     files. Defaults to the value of the "icon.directory" variable.
 *   - description: A description to be displayed instead of the filename.
 *   - attributes: An associative array of attributes to be placed in the a tag.
 */
function template_preprocess_minisite_link(array &$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);

  $file = $variables['file'];
  $options = [];

  $file_entity = ($file instanceof File) ? $file : File::load($file->fid);
  // @todo Wrap in file_url_transform_relative(). This is currently
  // impossible. As a work-around, we currently add the 'url.site' cache context
  // to ensure different file URLs are generated for different sites in a
  // multisite setup, including HTTP and HTTPS versions of the same site.
  // Fix in https://www.drupal.org/node/2646744.
  $variables['#cache']['contexts'][] = 'url.site';

  $link_text = $file_entity->getFilename();

  // Asset path is provided.
  if (isset($variables['asset_path'])) {
    // Asset path is a file.
    if (LegacyWrapper::isValidUri($variables['asset_path'])) {
      $url = file_create_url($variables['asset_path']);
    }
    // Asset path is an alias to a path.
    else {
      $url = Url::fromUserInput($variables['asset_path'])->toString();
    }

    // Use the description as the link text if available.
    if (!empty($variables['description'])) {
      $link_text = $variables['description'];
    }
  }
  // Falling back to the archive file.
  else {
    $url = file_create_url($file_entity->getFileUri());

    $mime_type = $file->getMimeType();
    // Set options as per anchor format described at
    // http://microformats.org/wiki/file-format-examples
    $options['attributes']['type'] = $mime_type . '; length=' . $file->getSize();

    // Classes to add to the file field for icons.
    $classes = [
      'file',
      // Add a specific class for each and every mime type.
      'file--mime-' . strtr($mime_type, ['/' => '-', '.' => '-']),
      // Add a more general class for groups of well known MIME types.
      'file--' . file_icon_class($mime_type),
    ];

    $variables['attributes']->addClass($classes);
  }

  // Set link title.
  $options['attributes']['title'] = $link_text;

  $variables['link'] = Link::fromTextAndUrl($link_text, Url::fromUserInput($url, $options));
}
