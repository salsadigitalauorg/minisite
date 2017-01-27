<?php

namespace Drupal\minisite\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'minisite' formatter.
 *
 * @FieldFormatter(
 *   id = "minisite_link",
 *   label = @Translation("Minisite link"),
 *   field_types = {
 *     "minisite"
 *   }
 * )
 */
class MinisiteFormatter extends GenericFileFormatter {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
        'minisite_link' => 'content',
      ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );

    $form['minisite_link'] = array(
      '#title' => t('Link Minisite to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('minisite_link'),
      '#options' => $link_types,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if minisite is linked.
    $minisite_link_setting = $this->getSetting('minisite_link');
    if (isset($link_types[$minisite_link_setting])) {
      $summary[] = $link_types[$minisite_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // Loop through items
    foreach ($items as $delta => $file) {
      // Set asset path.
      $file->entity->asset_path = $file->asset_path;
      // Send elements to theme.
      $elements[$delta] = array(
        '#theme' => 'minisite_link',
        '#file' => $file->entity,
        '#description' => $file->description,
        '#cache' => array(
          'tags' => $file->entity->getCacheTags(),
        ),
      );

    }

    return $elements;
  }
}
