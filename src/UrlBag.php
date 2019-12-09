<?php

namespace Drupal\minisite;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\minisite\Exception\UrlBagException;

/**
 * Class UrlBag.
 *
 * A container for URLs with helping methods.
 *
 * @package Drupal\minisite
 */
class UrlBag {

  /**
   * Defines part of URI which is asset directory.
   */
  const URI_PART_ASSET_DIR = 0;

  /**
   * Defines part of URI which is archive directory.
   */
  const URI_PART_ROOT_ARCHIVE_DIR = 1;

  /**
   * Defines part of URI which is path in archive.
   */
  const URI_PART_PATH_IN_ARCHIVE = 2;

  /**
   * Defines part of URI which is basename (document file name).
   */
  const URI_PART_BASENAME = 3;

  /**
   * The URI of the current file.
   *
   * @var string
   */
  protected $uri;

  /**
   * The base website URL.
   *
   * For example, http://somesite.com, https://somesite.com,
   * http://somesite.com/subdir etc.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * Parent alias as relative path.
   *
   * @var string
   */
  protected $parentAlias;

  /**
   * UrlBag constructor.
   *
   * @param string $uri
   *   The URI of the asset file.
   * @param string|null $base_url
   *   (optional) The base URL of the site. If not provided, the globally set
   *   base URL will be used.
   */
  public function __construct($uri, $base_url = NULL) {
    $this->uri = $uri;
    $this->baseUrl = $base_url ? $base_url : $this->getGlobalBaseUrl();
  }

  /**
   * Get URI.
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Get URL as a relative path.
   */
  public function getUrl() {
    return file_url_transform_relative(file_create_url($this->uri));
  }

  /**
   * Get URL as an absolute path.
   */
  public function getUrlAbsolute() {
    return static::toAbsolute($this->getUrl(), $this->baseUrl);
  }

  /**
   * Get alias for the current URL.
   *
   * @return string
   *   Alias, if parent alias was provided, or NULL if it was not (because
   *   we need to know if alias was explicitly set for this URI).
   */
  public function getAlias() {
    return isset($this->parentAlias) ? $this->parentAlias . '/' . $this->getRootDir() . '/' . $this->getPathInArchive() : NULL;
  }

  /**
   * Get absolute alias path.
   *
   * @return string|null
   *   Absolute alias path or NULL if parent alias was not set.
   */
  public function getAliasAbsolute() {
    $alias = $this->getAlias();

    return $alias ? static::toAbsolute($alias, $this->baseUrl) : NULL;
  }

  /**
   * Set alias.
   *
   * Note that this will "guess" different pieces of the url "bag" based on the
   * currently set URI.
   *
   * @param string $alias
   *   An alias to set.
   *
   * @throws \Drupal\minisite\Exception\UrlBagException
   *   If provided alias does not contain correct URI set in this bag.
   */
  public function setAlias($alias) {
    $path_in_archive = $this->getPathInArchive();
    if (strpos($alias, $path_in_archive) === FALSE) {
      throw new UrlBagException('Provided alias does not contain correct URI');
    }
    $parent_alias = str_replace($path_in_archive, '', $alias);
    $parent_alias_parts = array_filter(explode('/', $parent_alias));
    // Remove root dir.
    array_pop($parent_alias_parts);
    $this->setParentAlias(implode('/', $parent_alias_parts));
  }

  /**
   * Set parent alias.
   *
   * This sets the "prefix" part of the alias. Note that this can be empty
   * to unset the parent part of the alias.
   *
   * @param string $parent_alias
   *   Relative or absolute path.
   */
  public function setParentAlias($parent_alias) {
    $parent_alias = rtrim($parent_alias, '/');
    $this->parentAlias = static::toLocal($parent_alias, $this->baseUrl);
  }

  /**
   * Get parent alias.
   */
  public function getParentAlias() {
    return $this->parentAlias;
  }

  /**
   * Get parent alias as an absolute URL.
   */
  public function getParentAliasAbsolute() {
    return isset($this->parentAlias) ? static::toAbsolute($this->parentAlias, $this->baseUrl) : NULL;
  }

  /**
   * Get base URL.
   */
  public function getBaseUrl() {
    return $this->baseUrl;
  }

  /**
   * Get path in the archive.
   */
  public function getPathInArchive() {
    return static::getUriPart($this->getUri(), self::URI_PART_PATH_IN_ARCHIVE);
  }

  /**
   * Get root directory.
   */
  public function getRootDir() {
    return static::getUriPart($this->getUri(), self::URI_PART_ROOT_ARCHIVE_DIR);
  }

  /**
   * Get asset directory.
   */
  public function getAssetDir() {
    return static::getUriPart($this->getUri(), self::URI_PART_ASSET_DIR);
  }

  /**
   * Convert URL to local URL.
   *
   * @code
   * http://example.com => /
   * http://example.com/path => /path
   * http://example.com/path/subpath => /path/subpath
   * /path => /path
   * /path/subpath => /path/subpath
   * public://path => public://path
   * public://path/subpath => public://path/subpath
   * path=>/path
   * path/subpath=>/path/subpath
   * @endcode
   *
   * @param string $url
   *   URL to convert.
   * @param string $base_url
   *   Bae URL to use for conversion.
   *
   * @return string
   *   Converted URl as relative url.
   *
   * @throws \Drupal\minisite\Exception\UrlBagException
   *   If provided url is not within domain or does not contain a path.
   */
  protected static function toLocal($url, $base_url) {
    // Provided URL cannot be from another external domain.
    if (UrlHelper::isExternal($url) && !UrlHelper::externalIsLocal($url, $base_url)) {
      throw new UrlBagException('Provided external path points to another domain');
    }

    // Parsing URL with Core's parser to handle relative paths.
    $parsed = UrlHelper::parse($url);
    // Re-parsing the 'path' component again to extract actual path.
    $parsed = isset($parsed['path']) ? parse_url($parsed['path']) : $parsed;

    // Check that this is a file starting with a register wrapper scheme and
    // return the URL as-is.
    if (!empty($parsed['scheme'])) {
      if (in_array($parsed['scheme'], static::getLocalWrapperSchemas())) {
        return $url;
      }
    }

    if (empty($parsed['path'])) {
      throw new UrlBagException('Provided URL does not contain path');
    }

    $parsed_base = parse_url($base_url);
    $url = isset($parsed_base['path']) ? str_replace($parsed_base['path'], '', $parsed['path']) : $parsed['path'];

    return '/' . ltrim($url, '/');
  }

  /**
   * Convert current URL to absolute.
   *
   * @param string $url
   *   URL to convert.
   * @param string $base_url
   *   The base site URL.
   *
   * @return string
   *   Absolute URL.
   */
  protected static function toAbsolute($url, $base_url) {
    if (UrlHelper::isExternal($url)) {
      return $url;
    }

    // Parsing URL with Core's parser to handle relative paths.
    $parsed = UrlHelper::parse($url);
    // Re-parsing the 'path' component again to extract actual path.
    $parsed = isset($parsed['path']) ? parse_url($parsed['path']) : $parsed;

    // Check that this is a file starting with a register wrapper scheme and
    // return the URL as-is.
    if (!empty($parsed['scheme'])) {
      if (in_array($parsed['scheme'], static::getLocalWrapperSchemas())) {
        // Files never get their URLs aliased, so return as-is.
        return file_create_url($url);
      }
    }

    return rtrim($base_url, '/') . '/' . ltrim($url, '/');
  }

  /**
   * Helper to get declared file wrappers.
   */
  protected static function getLocalWrapperSchemas() {
    static $local_schemas;
    if (!$local_schemas) {
      $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
      $local_schemas = array_keys($stream_wrapper_manager->getWrappers(StreamWrapperInterface::LOCAL));
    }

    return $local_schemas;
  }

  /**
   * Get a part from the provided URI.
   *
   * @param string $uri
   *   The URI to assess. Note that URI must have UUID-like string.
   * @param int $part_name
   *   One of the part constants from this class.
   *
   * @return string
   *   URI part.
   *
   * @throws \Drupal\minisite\Exception\UrlBagException
   *   If incorrectly formatted URI provided.
   */
  protected static function getUriPart($uri, $part_name) {
    $parts = preg_split('/([a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-(?:8|9|a|b)[a-f0-9]{3}\-[a-f0-9]{12})/', $uri, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!$parts || count($parts) != 3) {
      throw new UrlBagException('Invalid URI provided');
    }

    $tail = array_pop($parts);
    $tail_parts = array_filter(explode('/', $tail));
    $parts[] = array_shift($tail_parts);
    $parts[] = implode('/', $tail_parts);

    if ($part_name == self::URI_PART_ASSET_DIR) {
      return $parts[0] . $parts[1];
    }

    if ($part_name == self::URI_PART_ROOT_ARCHIVE_DIR) {
      return ltrim($parts[2], '/');
    }

    if ($part_name == self::URI_PART_PATH_IN_ARCHIVE) {
      return ltrim($parts[3], '/');
    }

    if ($part_name == self::URI_PART_BASENAME) {
      return basename($parts[3]);
    }
  }

  /**
   * Get base site URL set globally.
   */
  protected function getGlobalBaseUrl() {
    global $base_url;

    return $base_url;
  }

}
