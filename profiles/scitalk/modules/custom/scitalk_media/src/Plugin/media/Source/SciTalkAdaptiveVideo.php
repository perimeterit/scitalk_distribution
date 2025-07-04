<?php

namespace Drupal\scitalk_media\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\Core\File\FileExists;
use function GuzzleHttp\json_encode;

/**
 * Scitalk Video entity media source.
 *
 * @MediaSource(
 *   id = "scitalk_adaptive",
 *   label = @Translation("SciTalk Adaptive Video"),
 *   allowed_field_types = {"string", "string_long", "link"},
 *   default_thumbnail_filename = "scitalk.png",
 *   description = @Translation("Provides business logic and metadata for SciTalk Adaptive Videos.")
 * )
 */
 class SciTalkAdaptiveVideo extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

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
      'id' => $this->t('Adaptive Video ID'),
    ];

    return $attributes;
  }

  
  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {

    switch ($attribute_name) {
      case 'thumbnail_uri':
      
        //we need to pull the image and deposit it locally in a folder so that we can reference it locally.
        //we are in control of our media entity, so we create the field_remote_thumbnail_url field on the entity
        $thumbnail_uri = !empty($media->field_remote_thumbnail_url) ? $media->get('field_remote_thumbnail_url')->getString() : '';
        $context = stream_context_create(array(
          'http' => array('timeout' =>10),
        ));
        $img =  $thumbnail_uri ? @file_get_contents($thumbnail_uri, false, $context) : '';  //@ used to suppress warning.  we don't want warnings!
        
        if($img === FALSE) { //404 errors should be trapped like this
          return FALSE;
        }
        elseif($img !== FALSE && strlen($img) < 1024) {  //ok, so its not 404'd.  But still too small.  Placeholder img perhaps?
          return FALSE;
        }
        else {
          //process it
          //let's create the temp directory it if it doesn't exist.
          $temp_path = 'public://scitalk-thumbs';

          //TODO: Prune the temp directory of aged images?
          //this will eventually be a large directory and could be done via simple script

          if (\Drupal::service('file_system')->prepareDirectory($temp_path, FileSystemInterface::CREATE_DIRECTORY)) {
            //TODO: Prune the temp directory of aged images?
            //this will eventually be a large directory and could be done via simple script
            $uri_path = parse_url($thumbnail_uri, PHP_URL_PATH);
            $arr = explode('/', $uri_path);
            $thumbnail_filename = $arr[count($arr) - 1];  //this is something.jpg // this should be generic enough to use the last piece of the url path
            
            //try to create a subfolder from the url path if there are at least 2 path subdirectories
            //this will help in those cases where the thumbnails have the same file name like in PIRSA:
            //e.g: /images/09040037/Slide_0001.jpg
            //the thumb would be stored as public://scitalk-thumbs/09040037/Slide_0001.jpg
            if (count($arr) > 1) {
              $uri_path_subfolder = $arr[count($arr) - 2] ?? NULL;
              if ($uri_path_subfolder) {
                $create_subfolder = $temp_path . '/' . $uri_path_subfolder;
                $created = \Drupal::service('file_system')->prepareDirectory($create_subfolder, FileSystemInterface::CREATE_DIRECTORY);
                if ($created) {
                  $thumbnail_filename = $uri_path_subfolder . '/' . $thumbnail_filename;
                }
              }
            }

            $file_temp = \Drupal::service('file.repository')->writeData($img, 'public://scitalk-thumbs/' . $thumbnail_filename, FileExists::Replace);
            return 'public://scitalk-thumbs/' . $thumbnail_filename;
          }
          else {
            return FALSE;
          }
        }
        
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    //this label shows up as the created field's name when you create a media type of SciTalk Video
    //machine name auto-generated
    
    return parent::createSourceField($type)
      ->set('label', 'Primary Adaptive Video Url');
  }
}
