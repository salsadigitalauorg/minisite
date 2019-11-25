<?php

namespace Drupal\minisite\Plugin\Field\FieldType;

use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * Class MinisiteItemList.
 *
 * Represents a configurable entity file field.
 *
 * @package Drupal\minisite\Plugin\Field\FieldType
 */
class MinisiteItemList extends FileFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);

    $result = $this->delegateMethod('postSave', $update);

    return (bool) array_filter($result);
  }

}
