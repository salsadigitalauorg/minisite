<?php

namespace Drupal\minisite;

use Drupal\Core\File\FileSystem;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Class LegacyWrapper.
 *
 * Helper class to resolve compatibility between Drupal core versions.
 *
 * @todo: Remove this file once 8.7.x is no longer supported (3/6/2020).
 *
 * @package Drupal\minisite
 */
class LegacyWrapper {

  /**
   * Finds all files that match a given mask in a given directory.
   *
   * @throws \RuntimeException
   *   If compatible function is not found.
   */
  public static function scanDirectory($dir, $mask, array $options = []) {
    if (method_exists(FileSystem::class, 'scanDirectory')) {
      /** @var \Drupal\Core\File\FileSystemInterface $fs */
      $fs = \Drupal::service('file_system');

      return $fs->scanDirectory($dir, $mask, $options);
    }

    if (function_exists('file_scan_directory')) {
      return file_scan_directory($dir, $mask, $options);
    }

    throw new \RuntimeException('Unable to find compatible function');
  }

  /**
   * Determines whether the URI has a valid scheme for file API operations.
   *
   * @throws \RuntimeException
   *   If compatible function is not found.
   */
  public static function isValidUri($uri) {
    if (method_exists(StreamWrapperManager::class, 'isValidUri')) {
      return \Drupal::service('stream_wrapper_manager')->isValidUri($uri);
    }

    if (function_exists('file_valid_uri')) {
      return file_valid_uri($uri);
    }

    throw new \RuntimeException('Unable to find compatible function');
  }

  /**
   * Returns the entity view display associated with a bundle and view mode.
   *
   * @throws \RuntimeException
   *   If compatible function is not found.
   */
  public static function getViewDisplay($entity_type, $bundle, $view_mode) {
    $dr = \Drupal::service('entity_display.repository');

    if (method_exists(get_class($dr), 'getViewDisplay')) {
      return $dr->getViewDisplay($entity_type, $bundle, $view_mode);
    }

    if (function_exists('entity_get_display')) {
      return entity_get_display($entity_type, $bundle, $view_mode);
    }

    throw new \RuntimeException('Unable to find compatible function');
  }

  /**
   * Returns the entity form display associated with a bundle and form mode.
   *
   * @throws \RuntimeException
   *   If compatible function is not found.
   */
  public static function getFormDisplay($entity_type, $bundle, $form_mode) {
    $dr = \Drupal::service('entity_display.repository');

    if (method_exists(get_class($dr), 'getFormDisplay')) {
      return $dr->getFormDisplay($entity_type, $bundle, $form_mode);
    }

    if (function_exists('entity_get_display')) {
      return entity_get_form_display($entity_type, $bundle, $form_mode);
    }

    throw new \RuntimeException('Unable to find compatible function');
  }

  /**
   * Returns the part of a URI after the schema.
   *
   * @param string $uri
   *   A stream, referenced as "scheme://target" or "data:target".
   *
   * @return string|bool
   *   A string containing the target (path), or FALSE if none.
   *   For example, the URI "public://sample/test.txt" would return
   *   "sample/test.txt".
   */
  public static function getTarget($uri) {
    if (is_callable(StreamWrapperManager::class, 'getTarget')) {
      return StreamWrapperManager::getTarget($uri);
    }

    if (function_exists('file_uri_target')) {
      return file_uri_target($uri);
    }

    throw new \RuntimeException('Unable to find compatible function');
  }

}
