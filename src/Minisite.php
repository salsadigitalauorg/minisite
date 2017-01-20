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
  static public function preSave(File $file, EntityInterface $entity) {
    dpm($file->getFileUri());
    dpm($file->get('uuid')->value);

    $minisite_asset_path = self::extractAsset($file);
    dpm($minisite_asset_path);

    return $minisite_asset_path;
  }
}
