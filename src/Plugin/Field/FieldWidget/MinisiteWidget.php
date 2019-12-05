<?php

namespace Drupal\minisite\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    $element['#theme'] = 'minisite_widget';

    $description = t(
      "Use the current page's URL (defined in URL path settings) as the mini-site base URL, so the mini-site seamlessly displays the page's URL path pre-fix.<br/>
      For example, if the alias of the page is <code>/my-page-alias</code> and the top directory in uploaded ZIP named <code>mysite</code>, the final URL will resolve to <code>@url</code>.<br/>
      Note that this will rewrite relative links within uploaded pages. See @help for more information about URL rewrite.",
      [
        '@url' => Url::fromUserInput('/my-page-alias/mysite/index.html', ['absolute' => TRUE])->toString(),
        '@help' => Link::fromTextAndUrl(t('Minisite help'), Url::fromUri('base:/admin/help/minisite'))->toString(),
      ]
    );

    // Add the additional fields.
    $element['options']['alias_status'] = [
      '#type' => 'checkbox',
      '#title' => t('Use URL alias'),
      '#default_value' => isset($item['options']['alias_status']) ? $item['options']['alias_status'] : FALSE,
      '#description' => $description,
      '#weight' => -11,
      '#access' => (bool) $item['fids'],
    ];

    return parent::process($element, $form_state, $form);
  }

}
