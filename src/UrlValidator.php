<?php

namespace Drupal\minisite;

use Drupal\Component\Utility\UrlHelper;

/**
 * Class UrlValidator.
 *
 * @package Drupal\minisite
 */
class UrlValidator {

  /**
   * Check if the URL is external.
   */
  public static function urlIsExternal($url) {
    return UrlHelper::isExternal($url);
  }

  /**
   * Check if the URL is a root-level relative URL.
   */
  public static function urlIsRoot($url) {
    return !static::urlIsExternal($url) && (substr($url, 0, 2) == './' || substr($url, 0, 1) == '/');
  }

  /**
   * Check if the URL is relative URL.
   */
  public static function urlIsRelative($url) {
    return substr($url, 0, 3) == '../';
  }

  /**
   * Check if the URL points to an index file.
   */
  public static function urlIsIndex($url, $index_file = 'index.html') {
    return basename($url) == $index_file;
  }

  /**
   * Convert root-level URL to relative URL with parent prefix support.
   */
  public static function rootToRelative($root_url, $parent = NULL, $prefix = NULL) {
    if (self::urlIsExternal($root_url) || !self::urlIsRoot($root_url)) {
      return $root_url;
    }

    if (substr($root_url, 0, 2) == './') {
      $root_url = substr($root_url, 2);
    }
    elseif (substr($root_url, 0, 1) == '/') {
      $root_url = substr($root_url, 1);
    }

    $parts = [];

    if ($prefix) {
      $parts[] = $prefix;
    }

    if ($parent) {
      $parts[] = $parent;
    }

    $parts[] = $root_url;

    return implode('/', $parts);
  }

  /**
   * Convert relative to root-level URL with parent prefix support.
   */
  public static function relativeToRoot($url, $parent) {
    if (self::urlIsExternal($url)) {
      return $url;
    }

    if (!self::urlIsRelative($url)) {
      if (substr($url, 0, 2) == './') {
        $url = substr($url, 2);
      }
      elseif (substr($url, 0, 1) == '/') {
        $url = substr($url, 1);
      }
    }

    // We assume that all relative links are correctly pointing to the root
    // of the archive, so we are removing all of them and adding a relative
    // path.
    $url = str_replace('../', '', $url);
    $url = rtrim($parent, '/') . '/' . ltrim($url, '/');
    $url = LegacyWrapper::isValidUri($url) ? file_url_transform_relative(file_create_url($url)) : $url;
    // Decode URL encoded in file_create_url().
    $url = rawurldecode($url);
    $url = '/' . ltrim($url, '/');

    return $url;
  }

}
