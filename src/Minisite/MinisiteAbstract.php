<?php

namespace Drupal\minisite\Minisite;

use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityInterface;
use Minisite\File as Archive;

class MinisiteAbstract {
  protected $archive = NULL;

  /**
   * MinisiteAbstract constructor.
   */
  public function __construct() {

  }

  /**
   * @param \Drupal\file\Entity\File $file
   * @return bool|static
   */
  static public function open(File $file) {
    $archive = new static();
    $archive_file = \Drupal::service('file_system')
      ->realpath($file->getFileUri());
    try {
      switch ($file->getMimeType()) {
        case 'application/zip':
          $archive->archive = new Archive($archive_file);
          $archive->archive->lists();
          $archive->archive->listsTree();
          break;

        default:
          $archive = FALSE;
      }
    } catch (\Exception $e) {
      $archive = FALSE;
    }

    return $archive;
  }

  /**
   * @param \Drupal\file\Entity\File $file
   * @return bool|string
   */
  static public function extractAsset(File $file) {
    $uuid = $file->get('uuid')->value;
    $minisite_asset_extract_path = 'public://' . MINISITE_ASSET_PATH . '/' . $uuid;
    try {
      $archive = self::open($file);
      $listing = $archive->archive->lists();
      $archive->archive->extract($minisite_asset_extract_path, $listing);
    } catch (\Exception $e) {
      return FALSE;
    }

    return $minisite_asset_extract_path . '/' . self::getTreeTop($file) . '/index.html';
  }

  /**
   * @param \Drupal\file\Entity\File $file
   * @return mixed
   */
  static public function getTreeTop(File $file) {
    $archive = self::open($file);
    $minisite_tree = $archive->archive->listsTree();
    $root_files = array_keys($minisite_tree);

    return $root_files[0];
  }

  /**
   * @return mixed
   */
  public function getArchive() {
    return isset($this->_archive) ? $this->_archive : FALSE;
  }

  /**
   * @param mixed $archive
   */
  public function setArchive($archive) {
    $this->_archive = $archive;
  }
}
