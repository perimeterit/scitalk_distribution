<?php
namespace Drupal\scitalk_base\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for scitalk base.
 */
class ScitalkBaseConfigForm extends ConfigFormBase {

  /** 
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'scitalk_base.settings';

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scitalk_base_settings_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['use_doi'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use DOI'),
        '#default_value' => $config->get('use_doi'),
        '#description' => $this->t('Enable integration with DataCite DOI'),
        '#attributes' => [
            'id' => 'use_doi',
        ],
    ];  

    $form['doi_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DOI Prefix'),
      //'#required' => TRUE,
      '#default_value' => $config->get('doi_prefix'),
      '#states' => [
        'visible' => [
            ':input[id="use_doi"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[id="use_doi"]' => ['checked' => TRUE],
        ],
      ],
    ];  
    
    $form['datacite_user'] = [
        '#type' => 'textfield',
        '#title' => $this->t('DataCite User'),
        // '#required' => TRUE,
        '#default_value' => $config->get('datacite_user'),
        '#states' => [
            'visible' => [
                ':input[id="use_doi"]' => ['checked' => TRUE],
            ],
            'required' => [
              ':input[id="use_doi"]' => ['checked' => TRUE],
            ],
        ],
    ];  
    
    $form['datacite_pwd'] = [
        '#type' => 'password',
        '#title' => $this->t('DataCite Password'),
        //'#required' => TRUE,
        '#default_value' => $config->get('datacite_pwd'),
        '#states' => [
            'visible' => [
                ':input[id="use_doi"]' => ['checked' => TRUE],
            ],
            'required' => [
                 ':input[id="use_doi"]' => ['checked' => TRUE],
           ],
        ],
    ];  
    
    $form['datacite_creator_institution'] = [
        '#type' => 'textfield',
        '#title' => $this->t('DataCite Creator Institution'),
        '#description' => $this->t('Use this field if using an organization as the creator field in DOI.'),
        '#default_value' => $config->get('datacite_creator_institution'),
        '#attributes' => [
            'id' => 'datacite_creator_institution',
        ],
        '#states' => [
            'visible' => [
                ':input[id="use_doi"]' => ['checked' => TRUE],
            ],
        ],
    ];

    $form['datacite_creator_institution_ror'] = [
        '#type' => 'textfield',
        '#title' => $this->t('DataCite Creator Institution ROR Registry ID'),
        '#field_prefix' => 'https://ror.org/',
        '#description' => $this->t('If using an organization as the creator, you can specify the Research Organization Registry ID in this field.<br>(See <a target="_blank" href="https://ror.org">https://ror.org</a>)'),
        '#default_value' => $config->get('datacite_creator_institution_ror'),
        '#states' => [
            //show this textfield only if the an institution has been entered
            'visible' => [
                ':input[id="datacite_creator_institution"]' => ['filled' => TRUE], //['value' => ''],
                ':input[id="use_doi"]' => ['checked' => TRUE],
            ],
        ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('use_doi', $form_state->getValue('use_doi'))
      ->set('doi_prefix', $form_state->getValue('doi_prefix'))
      ->set('datacite_user', $form_state->getValue('datacite_user'))
      ->set('datacite_pwd', $form_state->getValue('datacite_pwd'))
      ->set('datacite_creator_institution', $form_state->getValue('datacite_creator_institution'))
      ->set('datacite_creator_institution_ror', $form_state->getValue('datacite_creator_institution_ror'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}