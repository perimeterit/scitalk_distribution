<?php
namespace Drupal\scitalk_media\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for scitalk media.
 */
class SciTalkMediaConfigForm extends ConfigFormBase {

  /** 
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'scitalk_media.settings';

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scitalk_media_settings_form';
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

    $form['preroll_media_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preroll Video url'),
      '#description' => $this->t('The preroll video url.'),
      '#default_value' => $config->get('preroll_media_url'),
      '#attributes' => [
          'id' => 'preroll_media_url',
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
      ->set('preroll_media_url', $form_state->getValue('preroll_media_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}