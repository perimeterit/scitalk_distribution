<?php

namespace Drupal\scitalk_base\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;

use Drupal\scitalk_base\ScitalkServices\SciVideosIntegration;

/**
 * Class SciTalkBaseFieldsMapping.
 */
class SciTalkBaseFieldsMapping extends FormBase {

  protected $config;
  protected $rowsCounter = 0;
  protected $siteOptionsCount = 0;
  protected $siteOptions;
  protected $sciVideosOptions;
  protected $sciVideosIntegration;
  protected $entityTypeManager;
  protected $tempStoreFactory;
  protected $configFactory;
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scitalk_base_fields_mapping';
  }

  public function __construct(
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager,
    SciVideosIntegration $scivideos_integration,
    PrivateTempStoreFactory $tempStoreFactory
  ) {
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->sciVideosIntegration = $scivideos_integration;
    $this->tempStoreFactory = $tempStoreFactory;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('scitalk_base.scivideos_integrate'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mapping_type = NULL) {
    $form['#attached']['library'][] = 'scitalk_base/scitalk_base';
    // $form['#attached']['library'][] = 'scitalk_base/remove_mapping_row';

    $config_file = "scitalk_base.scitalk_base.{$mapping_type}";
    $this->config = $this->configFactory->get($config_file);
    $form_state->set('mapping_configuration', $mapping_type);

    $site_vocabulary = $this->config->get('site_vocabulary') ?? '';
    $this->siteOptions = $this->getSiteTermsOptions($site_vocabulary);
    $this->siteOptionsCount = count($this->siteOptions);

    $scivideo_vocabulary = $this->config->get('scivideos_vocabulary') ?? '';
    $this->sciVideosOptions = $this->getSciVideosTermsOptions($scivideo_vocabulary);

    $site_name = $this->getSiteName();
    $title = $site_name . ' to SciVideos Terms Mapping';

    $form['field_mappings_wrapper'] = [
      '#title' => $this->t($title),
      '#type' => 'fieldset',
    ];

    $field_mappings_wrapper = &$form['field_mappings_wrapper'];
    $field_mappings_wrapper['field_mappings'] = [
      '#tree' => TRUE,
      '#type' => 'container',
      '#prefix' => '<div id="edit-field-mappings">',
      '#suffix' => '</div>',
      '#attributes' => ['class' => ['scitalk_base_mapping_container']],
    ];

    $rows = &$field_mappings_wrapper['field_mappings'];

    $form['field_mappings_wrapper']['ajax_warning'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'edit-ajax-warning',
      ],
    ];

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    //Display existing mappings
    $site_terms = $this->config->get('term_mappings');
    $terms_added = count($site_terms);
    foreach ($site_terms as $term) {
      $rows[] = $this->getRow($form, $form_state, $term);
    }

    //display rows being added by user
    $last_id = 1;
    if ($terms_added) {
      $last_id = end($site_terms)['id'] + 1;
    }
    for ($i = 1; $i <= $this->rowsCounter && $i <= ($this->siteOptionsCount - $terms_added); $i++) {
      $rows[] = $this->getRow($form, $form_state, ['id' => $last_id++]);
    }

    $add_field_text = ($terms_added > 0) ? $this->t('Map more terms') : $this->t('Add a term mapping to get started');

    $form['buttons'] = [
      '#type' => 'container',
      '#tree' => true
    ];

    $form['buttons']['add'] = [
      '#value' => $add_field_text,
      '#type' => 'submit',
      '#limit_validation_errors' => [['buttons']],
      '#submit' => ['::addNewMappingType'],
      '#ajax' => [
        'callback' => [$this, 'mappingAddCallback'],
        'wrapper' => 'edit-field-mappings',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => ['class' => ['scitalk_base_config_save']],
    ];

    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => ['class' => ['scitalk_base_config_cancel']],
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelAddingMappings'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mappings = $form_state->getValue('field_mappings');
    if (!empty($mappings)) {
      //remove configs marked as "delete" or empty ones
      foreach ($mappings as $k => $map) {
        if ($map['ops']['delete'] == 'delete') {
          unset($mappings[$k]);
        }
        else if (empty($map['site_term_map_from']) && empty($map['scivideos_term_map_to'])) {
          unset($mappings[$k]);
        }
      }
  
      //find any mappings from site that are duplicates
      $site_term_ids = array_column($mappings, 'site_term_map_from');
      $unique_mappings = array_unique($site_term_ids);
      $duplicates = array_diff_assoc( $site_term_ids, $unique_mappings);
  
      foreach ($mappings as $k => $map) {
        $map_from = $map['site_term_map_from'];
        $map_to = $map['scivideos_term_map_to'];
  
        //set error that this mapping is duplicated
        if (in_array($map_from, $duplicates)) {
          $form_state->setError($form['field_mappings_wrapper']['field_mappings'][$k]['site_term_map_from'], 'This selection is duplicated');
        }
  
        $site_name = $this->getSiteName();
        //set error that one of the mapping is empty
        if (empty($map_from)) {
          $form_state->setError($form['field_mappings_wrapper']['field_mappings'][$k]['site_term_map_from'], "No value selected to map from {$site_name}");
        }
        else if (empty($map_to)) {
          $form_state->setError($form['field_mappings_wrapper']['field_mappings'][$k]['scivideos_term_map_to'], 'No value selected to map to SciVideos');
        }
  
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mapping_config = $form_state->get('mapping_configuration');
    $config_file = "scitalk_base.scitalk_base.{$mapping_config}";
   
    $values = $form_state->getValues();
    $mappings = [];
    foreach ($values['field_mappings'] as $idx =>  $map) {
      if (empty($map['site_term_map_from']) || empty($map['scivideos_term_map_to'])) {
        continue;
      }

      //delete the ones marked to delete
      if ($map['ops']['delete'] == 'delete') {
        continue;
      }

      $mappings[$idx] = [
        'site_term_id' => $map['site_term_map_from'],
        'scivideos_term_id' => $map['scivideos_term_map_to'],
        'id' => $idx
      ];
    }

    $update_config = $this->configFactory()->getEditable($config_file);
    
    $update_config->set('term_mappings', $mappings);
    $update_config->save();

    $this->messenger->addMessage('Mappings saved.');
  }

  /**
   * Ajax callback for adding a new mapping.
   */
  public function mappingAddCallback(&$form, FormStateInterface &$form_state) {
    return $form['field_mappings_wrapper']['field_mappings'];
  }

  /**
   * add new mapping type
   */
  public function addNewMappingType(&$form, FormStateInterface &$form_state) {
    $this->rowsCounter++; 
    $form_state->setRebuild(TRUE);
  }

  /**
   * Cancel handler
   */
  public function cancelAddingMappings(&$form, FormStateInterface &$form_state) {
    $url = Url::fromRoute('entity.scitalk_base.collection');
    $form_state->setRedirectUrl($url);
  }

  /**
   * Helper function to return an empty row for the field mapping form.
   */
  private function getRow($form, FormStateInterface $form_state, $term = NULL) {
    $values = &$form_state->getValues();

    $row['site_term_map_from'] = [
      '#title' => $this->t('Site Terms'),
      '#type' => 'select',
      '#options' => $this->siteOptions, 
      '#attributes' => ['class' => ['scitalk_base_site_term']],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $term['site_term_id'] ?? '',
    ];
    $row['scivideos_term_map_to'] = [
      '#title' => $this->t('SciVideos Terms'),
      '#type' => 'select',
      '#options' => $this->sciVideosOptions,
      '#attributes' => ['class' => ['scitalk_base_scivideos_term']],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $term['scivideos_term_id'] ?? '',
    ];

    $operations = [
      'delete' => $this->t('Delete'),
    ];

    $defaults = [];

    $row['ops'] = [
      // '#title' => $this->t('Delete'),
      '#type' => 'checkboxes',
      '#options' => $operations,
      '#default_value' => $defaults,
      '#attributes' => [ 'class' => ['scitalk_base_ops']],
    ];

    $row['#type'] = 'container';
    $row['#attributes'] = [
      'class' => ['scitalk_base_mapping_term', 'row', $term['id'] % 2 ? 'odd' : 'even']
    ];

    return $row;

  }

  /**
   * Fetch local Vocabulary terms
   */
  private function getSiteTermsOptions($vocabulary_name) {
    $options = [];
    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery();
    $query->condition('vid', $vocabulary_name);
    $tids = $query->execute();
    $terms = Term::loadMultiple($tids);
    foreach ($terms as $term) {
      $options[$term->uuid->value] = $term->name->value;
    }
    return $options;
  }

  /**
   * pull SciVideos Vocabulary terms
   */
  private function getSciVideosTermsOptions($vocabulary_name) {
    //check if in local storage
    $tempstore = $this->tempStoreFactory->get('scitalk_base');
    $storage = "scivideos_options_{$vocabulary_name}";
    if ($options = $tempstore->get($storage)) {
      return $options;
    }
    
    //fetch from SciVideos and store in local storage
    // @TODO: set an expiry time (default = 604800).
    $options = [];
    $scivideos_vocabulary_terms = $this->sciVideosIntegration->fetchVocabularyTerms($vocabulary_name);
    $scivideos_vocabulary_terms = json_decode($scivideos_vocabulary_terms);
    foreach ($scivideos_vocabulary_terms->data as $term) {
      $options[$term->id] = $term->attributes->name;
    }

    $tempstore->set($storage, $options);

    return $options;
  }

  /**
   * get site name
   */
  protected function getSiteName() {
    return $this->configFactory()->get('system.site')->get('name') ?? 'Local Site';
  }

}
