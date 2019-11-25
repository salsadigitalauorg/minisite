<?php

namespace Drupal\minisite;

use Drupal\minisite\Exception\AssetException;

/**
 * Class MinisiteAsset.
 *
 * A single Minisite asset.
 *
 * @package Drupal\minisite
 */
class MinisiteAsset {

  /**
   * Index entry point file name.
   */
  const INDEX_FILE = 'index.html';

  /**
   * URI of the source file for this asset.
   *
   * @var string
   */
  protected $source;

  /**
   * MinisiteAsset constructor.
   *
   * @param string $sourceUri
   *   Source file path.
   */
  public function __construct($sourceUri) {
    $this->setSource($sourceUri);
  }

  /**
   * Set source file URI.
   *
   * @param string $path
   *   The path to set as URI.
   *
   * @throws \Drupal\minisite\Exception\AssetException
   */
  public function setSource($path) {
    if (!is_readable($path)) {
      throw new AssetException('Unable to set asset source path "%s"', $path);
    }

    $this->source = $path;
  }

  /**
   * Get source path.
   *
   * @return string
   *   The source path.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Check if current asset is index entry point.
   *
   * @return bool
   *   TRUE if the asset is index entry point, FALSE otherwise.
   */
  public function isIndex() {
    return \Drupal::service('file_system')->basename($this->source) == self::INDEX_FILE;
  }

  /**
   * Delete asset, while also removing empty directories.
   */
  public function delete() {
    /** @var \Drupal\Core\File\FileSystem $fs */
    $fs = \Drupal::service('file_system');

    $fs->deleteRecursive($this->source);

    // Remove parent directories if there is no other files up until common
    // assets directory.
    $dirname = $this->source;
    while (($dirname = $fs->dirname($dirname)) && $dirname != Minisite::getCommonAssetDir()) {
      if (empty(file_scan_directory($dirname, '/.*/'))) {
        \Drupal::service('file_system')->deleteRecursive($dirname);
      }
    }
  }

}
