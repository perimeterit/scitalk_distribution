<?php

namespace Drupal\scitalk_feeds_cern_api_parser\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for CERN API fetchers.
 */
class FeedsCERNApiFetcherForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->plugin->getConfiguration();

    $form['import_talks_limit'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#title' => t('Limit the total number of imported talks'),
      '#description' => t('Specify a limit for the total number of talks to import from CERN.'),
      '#default_value' => $config['import_talks_limit'],
      '#required' => TRUE,
    );
    $form['results_per_page'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#max' => 50,
      '#title' => $this->t('Limit talks per API request'),
      '#description' => $this->t('Limit the number of retrieved talks per API request (talks per page).'),
      '#default_value' => $config['results_per_page'],
      '#required' => TRUE,
    );
    $form['request_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('Timeout in seconds to wait for an HTTP request to finish.'),
      '#default_value' => $config['request_timeout'],
      '#min' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Trim all values before saving.
    $values = $form_state->getValues();
    foreach ($values as &$value) {
      if (is_string($value)) {
        $value = trim($value);
      }
    }
    $this->plugin->setConfiguration($values);
  }

}
