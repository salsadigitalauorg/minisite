<?php

namespace Drupal\minisite\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * Class MinisiteItemList.
 *
 * Represents a configurable Minisite file field.
 *
 * @package Drupal\minisite\Plugin\Field\FieldType
 */
class MinisiteItemList extends FileFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);

    $result = [];

    // Item update or creation.
    if (!$this->isEmpty()) {
      $result = $this->delegateMethodForItemList('postSave', $this, $update);
    }
    // Items list is empty, so check original list of values.
    // If there are original values present - the value of the field was
    // removed (but the parent entity still exists), therefore we need to
    // remove the Minisite as well.
    // The parent class handles removal of associated managed files, but
    // Minisite also has expanded files and database records that need to be
    // removed as well.
    //
    // Note that we cannot rely on Drupal to call delete() method of the item
    // itself - that method only called when the host entity (node etc.) is
    // removed.
    else {
      $original_item_list = $this->getOriginalItemsList();
      if ($original_item_list) {
        $result = $this->delegateMethodForItemList('delete', $original_item_list);
      }
    }

    return (bool) array_filter($result);
  }

  /**
   * Get original item list for the current instance.
   *
   * @return null|\Drupal\Core\Field\FieldItemList
   *   The original item list for the same language as the current item list
   *   or NULL if the item list for the same language does not exist.
   */
  protected function getOriginalItemsList() {
    $entity = $this->getEntity();
    $original = $entity->original;
    $field_name = $this->getFieldDefinition()->getName();
    $langcode = $this->getLangcode();

    if (!$original || !$original->hasTranslation($langcode)) {
      return NULL;
    }

    return $original->getTranslation($langcode)->{$field_name};
  }

  /**
   * Delegate method for an items list.
   *
   * Works in the same way as
   * \Drupal\Core\Field\EntityReferenceFieldItemList::delegateMethod(), but
   * with supplied items list object.
   *
   * Any argument passed will be forwarded to the invoked method.
   *
   * @param string $method
   *   The method name to call for each item in the list.
   * @param \Drupal\Core\Field\FieldItemList $item_list
   *   The items list to traverse.
   *
   * @return array
   *   An array of results keyed by delta.
   *
   * @see \Drupal\Core\Field\FieldItemList::delegateMethod()
   */
  protected function delegateMethodForItemList($method, FieldItemList $item_list) {
    $result = [];

    $args = array_slice(func_get_args(), 2);

    foreach ($item_list->list as $delta => $item) {
      // call_user_func_array() is way slower than a direct call so we avoid
      // using it if have no parameters.
      $result[$delta] = $args ? call_user_func_array([$item, $method], $args) : $item->{$method}();
    }

    return $result;
  }

}
