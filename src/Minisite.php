<?php

/**
 * @file
 * Minisite class.
 */

namespace Drupal\minisite;

use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityInterface;
use Drupal\minisite\Minisite\MinisiteAbstract;

/**
 * Class Minisite
 * @package Drupal\minisite
 */
class Minisite extends MinisiteAbstract {
  /**
   * Presave minisite assets.
   *
   * @param \Drupal\file\Entity\File $file
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return bool|string
   */
  static public function preSave(File $file, EntityInterface $entity) {
    $minisite_asset_path = self::extractAsset($file);

    return !empty($minisite_asset_path) ? $minisite_asset_path : FALSE;
  }
}
