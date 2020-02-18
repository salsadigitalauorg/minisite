<?php

namespace Drupal\minisite;

/**
 * Class Asset.
 *
 * A single Minisite asset.
 *
 * @package Drupal\minisite
 */
class AssetContainer {

  /**
   * Array of assets.
   *
   * @var Asset[]
   */
  protected $assets = [];

  /**
   * AssetContainer constructor.
   *
   * @param null|array $assets
   *   (optinal) Array of assets.
   */
  public function __construct($assets = NULL) {
    if ($assets) {
      $this->assets = $assets;
    }
  }

  /**
   * Add asset to the list.
   */
  public function add($entity_type,
                      $entity_bundle,
                      $entity_id,
                      $entity_language,
                      $field_name,
                      $file_uri) {

    $asset = new Asset(
      $entity_type,
      $entity_bundle,
      $entity_id,
      $entity_language,
      $field_name,
      $file_uri);

    // We need to check if provided asset URI already exists and use currently
    // provided asset fields to allow updating of existing asset in the DB.
    $existing_asset = Asset::loadByUri($file_uri);
    if ($existing_asset) {
      $asset->setId($existing_asset->id());
    }

    $this->assets[$file_uri] = $asset;
  }

  /**
   * Update asset aliases.
   *
   * @param string $alias_prefix
   *   Alias prefix.
   */
  public function updateAliases($alias_prefix) {
    foreach ($this->assets as $asset) {
      $asset->setAliasPrefix($alias_prefix);
    }
  }

  /**
   * Get index asset.
   *
   * @return \Drupal\minisite\Asset|null
   *   Instance of the index asset or NULL if no index asset is found.
   */
  public function getIndexAsset() {
    foreach ($this->assets as $asset) {
      if ($asset->isIndex()) {
        return $asset;
      }
    }

    return NULL;
  }

  /**
   * Get a URI of the index asset.
   *
   * @return null|string
   *   The URI of the index asset or NULL if no asset found.
   */
  public function getIndexAssetUri() {
    $asset = $this->getIndexAsset();

    return $asset ? $asset->getUri() : NULL;
  }

  /**
   * Save all assets.
   */
  public function saveAll() {
    foreach ($this->assets as $asset) {
      $asset->save();
    }
  }

  /**
   * Delete all assets.
   */
  public function deleteAll() {
    foreach ($this->assets as $k => $asset) {
      $asset->delete();
      unset($this->assets[$k]);
    }
  }

}
