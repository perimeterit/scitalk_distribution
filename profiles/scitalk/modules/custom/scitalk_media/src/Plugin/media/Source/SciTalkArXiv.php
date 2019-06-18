<?php

namespace Drupal\scitalk_media\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use function GuzzleHttp\json_encode;

/**
 * Scitalk ArXiv entity media source.
 *
 * @MediaSource(
 *   id = "scitalk_arxiv",
 *   label = @Translation("SciTalk ArXiv Links"),
 *   allowed_field_types = {"string", "string_long", "link"},
 *   default_thumbnail_filename = "scitalk.png",   
 *   description = @Translation("Provides business logic and metadata for SciTalk ArXiv links.")
 * )
 */
 class SciTalkArXiv extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  
  //TODO: figure out if there's a validation required
  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = [
    '*' => 'id',  //at this point, seems to me to just take it all without validation...
  ];

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   Config field type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => '',
      'generate_thumbnails' => TRUE,  //we're not checking for default configs right now.  This left here for the time being if in the future we want to config it.
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = [  //these items show up in the initial configuration of the media type.
      'id' => $this->t('ArXiv ID'),
    ];

    return $attributes;
  }

  
  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {

    switch ($attribute_name) {
      case 'thumbnail_uri':
        
       //for now, we'll just return the parent
       //there's really nothing for us to fetch for thumbnails.. no?
       return parent::getMetadata($media, $attribute_name);
       
       
      
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

   
   
    return $form;
  }

  
  //TODO: These would reference the src/Plugin/Validation/Constraint files that would be named something like
  //PirsaVideoLinkConstraint.php   see the twitter validation as an example.. left in for reference
  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [
      //'TweetEmbedCode' => [],
      //'TweetVisible' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    //this label shows up as the created field's name when you create a media type of SciTalk ArXiv
    //machine name auto-generated
    
    return parent::createSourceField($type)
      ->set('label', 'ArXiv Reference Number');
  }

  


}
