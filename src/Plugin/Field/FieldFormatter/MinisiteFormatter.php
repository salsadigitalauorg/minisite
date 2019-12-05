<?php

namespace Drupal\minisite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\minisite\Minisite;

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

  use StringTranslationTrait;

  /**
   * Render as a link to content (first index file of the minisite).
   */
  const LINK_TO_CONTENT = 'content';

  /**
   * Render as a link to uploaded file.
   */
  const LINK_TO_FILE = 'file';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'minisite_link' => self::LINK_TO_CONTENT,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Allow to select what the displayed uploaded file link is linked to.
    $form['minisite_link'] = [
      '#title' => $this->t('Link Minisite to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('minisite_link'),
      '#options' => [
        self::LINK_TO_CONTENT => $this->t('Content'),
        self::LINK_TO_FILE => $this->t('File'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $link_types = [
      self::LINK_TO_CONTENT => $this->t('Link to Content'),
      self::LINK_TO_FILE => $this->t('Link to File'),
    ];

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
    $elements = [];

    $minisite = Minisite::createInstance($items);
    if ($minisite) {
      $elements[0] = [
        '#theme' => 'minisite_link',
        '#file' => $minisite->getArchiveFile(),
        '#asset_path' => $minisite->getIndexAssetUrl(),
        '#description' => $minisite->getDescription(),
        '#cache' => [
          'tags' => $minisite->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

}
