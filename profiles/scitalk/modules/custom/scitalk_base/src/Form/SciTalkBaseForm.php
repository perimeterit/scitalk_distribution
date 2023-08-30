<?php

namespace Drupal\scitalk_base\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

use Drupal\scitalk_base\ScitalkServices\SciVideosIntegration;

/**
 * Class SciTalkBaseForm.
 */
class SciTalkBaseForm extends EntityForm {

  protected $sciVideosIntegration;
  protected $tempStoreFactory;
  protected $configFactory;
  protected $entityTypeManager;

  public function __construct(SciVideosIntegration $scivideos_integration, PrivateTempStoreFactory $tempStoreFactory, ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager) {
    $this->sciVideosIntegration = $scivideos_integration;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;

  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('scitalk_base.scivideos_integrate'),
      $container->get('tempstore.private'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attached']['library'][] = 'scitalk_base/scitalk_base';
    $form['#title'] = t('Add a Vocabulary Mapping to SciVideos');

    $scitalk_base = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mapping Name'),
      '#maxlength' => 255,
      '#default_value' => $scitalk_base->label(),
      '#description' => $this->t("Name to identify this mapping."),
      '#required' => TRUE,
      // '#disabled' => TRUE,
      '#attributes' => [
        'readonly' => 'readonly',
      ],
      '#weight' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $scitalk_base->id(),
      '#machine_name' => [
        'exists' => '\Drupal\scitalk_base\Entity\SciTalkBase::load',
      ],
      '#disabled' => !$scitalk_base->isNew(),
      '#weight' => 40,
      '#attributes' => [
        'readonly' => 'readonly',
      ],
    ];

    //local site taxonomy
    $mapping = $this->entity;

    $form['site_vocabulary'] = [
      '#title' => $this->t('Local Vocabularies List'),
      '#type' => 'details',
      '#attributes' => [
        'id' => 'edit-site-vocabulary',
      ],
      '#open' => $mapping->isNew(),
    ];

    $site_vocabulary = '';
    if (!empty($form_state->getValues()) && !empty($form_state->getValue('site_vocabulary_list'))) {
      $site_vocabulary = $form_state->getValue('site_vocabulary_list');
    }
    elseif ($mapping->getSiteVocabulary()) {
      $site_vocabulary = $mapping->getSiteVocabulary();
    }

    $entity_types = $this->getVocabularyOptions();
    $form['site_vocabulary']['site_vocabulary_value'] = [
      '#title' => $this->t('Site Vocabularies'),
      '#id' => 'edit-site-vocabulary-type',
      '#type' => 'select',
      '#description' => $this->t('Select a local vocabulary to map from.'),
      '#options' => $entity_types,
      '#default_value' => $site_vocabulary,
      '#required' => TRUE,
      '#weight' => 10,
      '#empty_option' => $this->t('- Select -'),
      // '#ajax' => [
      //   'callback' => [$this, 'bundleCallback'],
      //   'event' => 'change',
      //   'wrapper' => 'drupal_bundle',
      // ],
    ];


    $form['scivideos_vocabulary'] = [
      '#title' => $this->t('SciVideos Vocabularies List'),
      '#id' => 'edit-salesforce-object',
      '#type' => 'details',
      '#open' => $mapping->isNew(),
    ];

    $scivideos_vocabulary_value = '';
    if (!empty($form_state->getValues()) && !empty($form_state->getValue('scivideos_vocabulary_type'))) {
      $scivideos_vocabulary_value = $form_state->getValue('scivideos_vocabulary_type');
    }
    elseif ($mapping->getSciVideosVocabulary()) {
      $scivideos_vocabulary_value = $mapping->getSciVideosVocabulary();
    }

    $form['scivideos_vocabulary']['scivideos_vocabulary_value'] = [
      '#title' => $this->t('SciVideos Vocabularies'),
      '#id' => 'edit-salesforce-object-type',
      '#type' => 'select',
      '#description' => $this->t('Select a SciVideos vocabulary to map to.'),
      '#default_value' => $scivideos_vocabulary_value,
      '#options' => $this->getSciVideosVocabularyOptions(),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select -'),
      '#weight' => 20,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {   
    $scitalk_base = $this->entity;
    $vals = $form_state->getValues();

    $scitalk_base->setSiteVocabulary($vals['site_vocabulary_value']);
    $scitalk_base->setSciVideosVocabulary($vals['scivideos_vocabulary_value']);

    $status = $scitalk_base->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label SciTalk Base.', [
          '%label' => $scitalk_base->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label SciTalk Base.', [
          '%label' => $scitalk_base->label(),
        ]));
    }
 
    $url = Url::fromRoute('scitalk_base.scitalk_base_fields_mapping', ['mapping_type' => $this->entity->id()]);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Fetch local Vocabularies
   */
  private function getVocabularyOptions() {
    $existing_mappings = $this->getExistingMappings();
    $options = [];
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $vocabulary) {
      //skip this option if it has already been mapped
      if (in_array($vocabulary->id(), $existing_mappings)) {
        continue;
      }
      $options[$vocabulary->id()] = $vocabulary->label();
    }
    return $options;
  }

  /**
   * pull SciVideos Vocabularies
   */
  private function getSciVideosVocabularyOptions() {
    //make sure SciVideos integration has been enabled
    $config = $this->configFactory->get('scitalk_base.settings');
    $scivideos_integration_on = $config->get('enable_scivideos_integrate') ?? FALSE;
    if (!$scivideos_integration_on) {
      $this->messenger()->addError("You must first enable SciVideos Integration to use this form!");
      return [];
    }

    //check if in local storage
    $tempstore = $this->tempStoreFactory->get('scitalk_base');
    $storage = "scivideos_vocabularies_options";
    if ($options = $tempstore->get($storage)) {
      return $options;
    }

    //fetch from SciVideos and store in local storage
    // @TODO: set an expiry time (default = 604800).
    $options = [];
    $scivideos_vocabularies = $this->sciVideosIntegration->fetchVocabularies();
    $scivideos_vocabularies = json_decode($scivideos_vocabularies);
    foreach ($scivideos_vocabularies->data as $vocabulary) {
      // $options[$vocabulary->id] = $vocabulary->attributes->name;
      $options[$vocabulary->attributes->drupal_internal__vid] = $vocabulary->attributes->name;
    }

    $tempstore->set($storage, $options);

    return $options;
  }

  /**
   * get existing scitalk base config entities
   */
  private function getExistingMappings() {
    $list = $this->entityTypeManager->getStorage('scitalk_base')->loadMultiple();
    $existing_mappings = [];
    foreach ($list as $k => $itm) {
      $existing_mappings[] = $k;
    }
    return $existing_mappings;
  }

}
