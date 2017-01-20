<?php

namespace Drupal\minisite\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

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
  public static function defaultSettings() {
    return array(
        'progress_indicator' => 'throbber',
      ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    return $element;
  }

  /**
   * Overrides \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();
    $file_upload_help = array(
      '#theme' => 'file_upload_help',
      '#description' => '',
      '#upload_validators' => $elements[0]['#upload_validators'],
      '#cardinality' => $cardinality,
    );
    if ($cardinality == 1) {
      // If there's only one field, return it as delta 0.
      if (empty($elements[0]['#default_value']['fids'])) {
        $file_upload_help['#description'] = $this->getFilteredDescription();
        $elements[0]['#description'] = \Drupal::service('renderer')
          ->renderPlain($file_upload_help);
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

    $field_settings = $this->getFieldSettings();

    // If not using custom extension validation, ensure this is an archive file.
    $supported_extensions = array('zip');
    $extensions = isset($element['#upload_validators']['file_validate_extensions'][0]) ? $element['#upload_validators']['file_validate_extensions'][0] : implode(' ', $supported_extensions);
    $extensions = array_intersect(explode(' ', $extensions), $supported_extensions);
    $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);

    // Add properties needed by process() method.

    return $element;
  }

  /**
   * Form API callback: Processes a minisite field element.
   *
   * Expands the minisite type to include the additional fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    $element['#theme'] = 'minisite_widget';

    // Add the additional fields.
    $element['options']['minisite_alias_status'] = array(
      '#description' => t('Optionally use current page URL (defined in URL path settings) as minisite base URL.'),
      '#type' => 'checkbox',
      '#title' => t('Minisite URL alias (experimental)'),
      '#default_value' => isset($item['options']['minisite_alias_status']) ? $item['options']['minisite_alias_status'] : '',
      '#weight' => -11,
      '#access' => (bool) $item['fids'],
    );

    return parent::process($element, $form_state, $form);
  }
}
