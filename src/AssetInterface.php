<?php

namespace Drupal\minisite;

/**
 * Interface AssetInterface.
 *
 * @package Drupal\minisite
 */
interface AssetInterface {

  /**
   * Index entry point file name.
   *
   * Assets with this file name are considered to be a starting page when
   * creating links to the minisite.
   */
  const INDEX_FILE = 'index.html';

  /**
   * Instantiate class from an array of values.
   *
   * @param array $values
   *   Array of values to instantiate the field.
   *
   * @return \Drupal\minisite\Asset
   *   An instance of the class.
   */
  public static function fromValues(array $values);

  /**
   * Load asset by id.
   *
   * @param int $id
   *   Asset id.
   *
   * @return \Drupal\minisite\Asset|null
   *   Class instance or NULL if asset cannot be loaded.
   */
  public static function load($id);

  /**
   * Load asset by URI location.
   *
   * Note that this function does not check if the asset file at provided URI
   * actually exists.
   *
   * @param string $uri
   *   URI of the asset to load by.
   *
   * @return \Drupal\minisite\Asset|null
   *   Class instance or NULL if asset cannot be loaded.
   */
  public static function loadByUri($uri);

  /**
   * Load asset by alias.
   *
   * @param string $alias
   *   Alias of the asset to load by.
   *
   * @return \Drupal\minisite\Asset|null
   *   Class instance or NULL if asset cannot be loaded.
   */
  public static function loadByAlias($alias);

  /**
   * Load all assets.
   *
   * @return \Drupal\minisite\Asset[]
   *   Array of all available assets.
   */
  public static function loadAll();

  /**
   * Save asset to the database.
   *
   * If internal $id is set, the asset will be updated, otherwise it will be
   * created.
   *
   * @return int|null
   *   ID of the created or updated asset. NULL if asset has not been saved to
   *   the database.
   */
  public function save();

  /**
   * Delete asset, while also removing empty directories.
   *
   * If the asset is the last one in the directory, the directory will be
   * recursively removed up to the common asset storage directory.
   */
  public function delete();

  /**
   * Render asset.
   *
   * @return string
   *   Rendered asset as content.
   */
  public function render();

  /**
   * Get asset id.
   *
   * @return int|null
   *   Asset ID or NULL.
   */
  public function id();

  /**
   * Set asset ID.
   *
   * @param int $id
   *   The ID to set.
   */
  public function setId($id);

  /**
   * Get asset URI.
   *
   * @return string
   *   The asset URI within the file system.
   */
  public function getUri();

  /**
   * Get asset URL.
   *
   * @return string
   *   The asset URL as public relative URL or an alias.
   */
  public function getUrl();

  /**
   * Get asset alias.
   *
   * In most cases, getUrl() should be used unless an alias should be explicitly
   * retrieved.
   *
   * @return string
   *   The full alias of the asset as a relative URL.
   */
  public function getAlias();

  /**
   * Set an alias for an asset.
   *
   * @param string $alias
   *   Alias to access asset as relative or absolute path.
   */
  public function setAlias($alias);

  /**
   * Set an alias for an asset.
   *
   * Note that we are setting only allowed part of the asset alias.
   *
   * @param string $parent_alias
   *   Parent alias as relative or absolute path.
   */
  public function setAliasPrefix($parent_alias);

  /**
   * Get asset language.
   *
   * @return string
   *   Asset language.
   */
  public function getLanguage();

  /**
   * Get asset MIME type.
   *
   * @return string
   *   Asset MIME type.
   */
  public function getMimeType();

  /**
   * Set mime type.
   *
   * @param string $mime_type
   *   The file mime type.
   */
  public function setMimeType($mime_type);

  /**
   * Get asset size.
   *
   * @return int
   *   Asset file size in bytes.
   */
  public function getSize();

  /**
   * Set asset size.
   *
   * @param int $size
   *   The size in bytes.
   */
  public function setSize($size);

  /**
   * Check if the current asset is index entry point.
   *
   * @return bool
   *   TRUE if the asset is index entry point, FALSE otherwise.
   */
  public function isIndex();

  /**
   * Check if asset is a document and can be served as a page.
   */
  public function isDocument();

  /**
   * The maximum age for which this object may be cached.
   *
   * @return int
   *   The maximum time in seconds that this object may be cached.
   */
  public function getCacheMaxAge();

}
