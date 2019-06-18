<?php

namespace Drupal\jw_player\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure search settings for this site.
 */
class JwplayerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jwplayer_main_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jw_player.settings'];
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jw_player.settings');
    $versions = [6 => 6, 7 => 7];
    $url = 'https://dashboard.jwplayer.com/#/players/downloads';

    $form['jw_player_version'] = array(
      '#type' => 'select',
      '#title' => t('Player version'),
      '#options' => $versions,
      '#default_value' => $config->get('jw_player_version'),
      '#description' => t('Select the version of JWPlayer you are using.'),
    );

    $form['jw_player_hosting'] = [
      '#type' => 'radios',
      '#title' => t('Hosting type'),
      '#options' => [
        'self' => $this->t('Self-Hosted'),
        'cloud' => $this->t('Cloud-Hosted'),
      ],
      '#default_value' => $config->get('cloud_player_library_url') ? 'cloud' : 'self',
      '#description' => t('Choose if JW Player will be downloaded and self-hosted, or the site will use JW Player\'s cloud-hosting service.'),
      '#states' => [
        'visible' => [
          [
            ['select[name="jw_player_version"]' => ['value' => '6']],
            ['select[name="jw_player_version"]' => ['value' => '7']],
          ],
        ],
      ],
    ];

    $form['jw_player_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Self-Hosted Player License Key'),
      '#description' => $this->t('Enter your key here. You can retrieve your license key from <a href="@url" target="_blank">your downloads page at jwplayer.com</a>.', array(
        '@url' => $url,
      )),
      '#default_value' => $config->get('jw_player_key'),
      '#states' => [
        'visible' => [
          [
            [
              'select[name="jw_player_version"]' => ['value' => '6'],
              ':input[name="jw_player_hosting"]' => ['value' => 'self']
            ],
            [
              'select[name="jw_player_version"]' => ['value' => '7'],
              ':input[name="jw_player_hosting"]' => ['value' => 'self']
            ],
          ],
        ],
      ],
    );

    $form['cloud_player_library_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cloud Player Library Url'),
      '#description' => $this->t('You can get the url for your cloud hosted player at <a href="@url" target="_blank">your downloads page at jwplayer.com</a>. After choosing your player, simply copy and enter the whole url from the field "Cloud Player Library Url" here. If you are using the cloud hosted player, the self hosted files will not be loaded.', array(
        '@url' => $url,
      )),
      '#default_value' => $config->get('cloud_player_library_url'),
      '#states' => [
        'visible' => [
          [
            [
              'select[name="jw_player_version"]' => ['value' => '6'],
              ':input[name="jw_player_hosting"]' => ['value' => 'cloud']
            ],
            [
              'select[name="jw_player_version"]' => ['value' => '7'],
              ':input[name="jw_player_hosting"]' => ['value' => 'cloud']
            ],
          ],
        ],
      ],
    );

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    switch ($form_state->getValue('jw_player_hosting')) {
      case '':
        $form_state->setErrorByName('jw_player_hosting', t('Hosting type is required.'));
        break;
      case 'self':
        if ($form_state->getValue('jw_player_key') == "" && $form_state->getValue('jw_player_version') == '7') {
          $form_state->setErrorByName('jw_player_key', t('Self-Hosted Player License Key is required when Hosting type is "Self-Hosted".'));
        }
        break;
      case 'cloud':
        if ($form_state->getValue('cloud_player_library_url') == "") {
          $form_state->setErrorByName('cloud_player_library_url', t('Default Cloud Player Url is required when Hosting type is "Cloud-Hosted".'));
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('jw_player.settings');
    if ($form_state->getValue('jw_player_hosting') == 'self') {
      $config->set('jw_player_key', $form_state->getValue('jw_player_key'));
      $config->clear('cloud_player_library_url');
    }
    else {
      $config->set('cloud_player_library_url', $form_state->getValue('cloud_player_library_url'));
      $config->clear('jw_player_key');
    }
    $config->set('jw_player_version', $form_state->getValue('jw_player_version'));
    $config->save();

  }

}
