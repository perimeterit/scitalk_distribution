<?php

namespace Drupal\id_link_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'id_url_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "id_url_formatter",
 *   label = @Translation("ID to URL Formatter"),
 *   field_types = {
 *     "string",
 *     "integer",
 *   }
 * )
 */
class IDLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $base_url = $this->getSetting('id_base_url');

    if (substr($base_url, -1) != '/') {
      $base_url .= '/';
    }

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'target' => '_blank: ',
          'href' => $base_url . $item->value
          //'href' => $base_url . urlencode($item->value)
        ],
        '#value' => $this->t('@url', ['@url' => $item->value]),
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TRUE;
  }

   /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'id_base_url' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['id_base_url'] = [
      '#title' => $this->t('Choose Base URL for this field'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('id_base_url'),
      '#description' => t('This URL will be used as the domain to create the link for this field.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('id_base_url') ? t('Base URL set') : t('No base URL set');
    return $summary;
  }

}
