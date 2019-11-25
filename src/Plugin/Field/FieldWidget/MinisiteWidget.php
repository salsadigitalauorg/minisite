<?php

namespace Drupal\minisite\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\minisite\MinisiteInterface;

/**
 * Plugin implementation of the 'minisite_default' widget.
 *
 * @FieldWidget(
 *   id = "minisite_default",
 *   label = @Translation("Minisite"),
 *   field_types = {
 *     "minisite"
 *   }
 * )
 */
class MinisiteWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $file_upload_help = [
      '#theme' => 'file_upload_help',
      '#description' => '',
      '#upload_validators' => $elements[0]['#upload_validators'],
      '#cardinality' => $cardinality,
    ];

    if ($cardinality == 1) {
      // If there's only one field, return it as delta 0.
      if (empty($elements[0]['#default_value']['fids'])) {
        $file_upload_help['#description'] = $this->getFilteredDescription();
        $elements[0]['#description'] = \Drupal::service('renderer')->renderPlain($file_upload_help);
      }
    }
    else {
      $elements['#file_upload_description'] = $file_upload_help;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // If standard file extension validation, enforce our supported archive
    // extensions.
    if (isset($element['#upload_validators']['file_validate_extensions'][0])) {
      $element['#upload_validators']['file_validate_extensions'][0] = MinisiteInterface::SUPPORTED_ARCHIVE_EXTENSIONS;
    }

    // Add archive format validator.
    $element['#upload_validators']['minisite_validate_archive'][] = $this->fieldDefinition->getSetting('minisite_extensions');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element['#theme'] = 'minisite_widget';

    return parent::process($element, $form_state, $form);
  }

}
