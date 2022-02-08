<?php

namespace Drupal\scitalk_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\Entity\MediaType;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jw_player\Entity\Jw_player;
use Drupal\scitalk_media\Plugin\media\Source\SciTalkVideo;
use Drupal\scitalk_media\Plugin\media\Source\SciTalkArXiv;


/**
 * Plugin implementation of the 'SciTalk_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "scitalk_embed",
 *   label = @Translation("Scitalk embed"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SciTalkEmbedFormatter extends FormatterBase {

  
   /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) { 
    //placeholder if we need to generate any meta-data for the display
  }
  
  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    //not overriding anything here, so we'll just call the parent.
    $elements = parent::view($items, $langcode);
    return $elements;
  }
  
   
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $theme = 'scitalk_media_interimhd'; //just a base theme file to use
    $video_attributes = [];
    $node = $items->getEntity();
    $field_name = $items->getName();
    $entity_id = $node->{$field_name}->target_id ?? '';
    $media_entity = entity_load('media', $entity_id);
    
    
    //we're ensuring that this is a SciTalkVideo type with this IF.  However, we've got a few media types associated with SciTalk.  
    //namely the video and arxiv links.  We'll handle them both with this formatter.
    if ($media_entity && (method_exists($media_entity, 'getSource')) && ($source = $media_entity->getSource()) && $source instanceof SciTalkVideo) {  //only care if this is the SciTalk media type
      $conf = $source->getConfiguration();
      //we now get which field we're using as the URI
      $uri_field = $this->getSetting('uri_field');
      if($uri_field == 'none') {
        //TODO:  provide a broken link video URI here..  is this a config option?
        //using the PI Youtube home channel vid:  https://www.youtube.com/watch?v=nBnTr8rUWmY
        
        $uri = 'https://www.youtube.com/watch?v=nBnTr8rUWmY';
      }
      else {
        //uri field is stored as media.bundle.field
        $fieldarray = explode('.', $uri_field);
        $uri = $media_entity->get($fieldarray[2])->getString();
      }
      $thumbnail = '/core/themes/bartik/logo.svg';  //this is the thumbnail default image.  Just the bartik drupal logo for now.
      $theme = 'scitalk_media_' . strtolower($conf['scitalk_video_source']);
      
      //TODO:  is this truly a configuration or is this a function of the field definitions?
      //suggestion is that we don't configure multiple scitalk video types and just use one that has 
      //options to extend the type and we allow for templates to be easily created.
      switch(strtolower($conf['scitalk_video_source'])) {
        case 'interimhd':
          $thumbnail = $media_entity->get('field_remote_thumbnail_url')->getString();  //we know this machine name as we created it.
          $video_attributes = [];  //populate this with the separate attribs for the videos
          break;
        
        //TODO:  Here's where we figure out the Wowza requirements.
         case 'wowza':
            $video_attributes = [];
          break;
          
        default:
          $theme = 'scitalk_media_interimhd';  //default to interimhd I suppose..
          $video_attributes = [];  //we don't know what this is, so leave it as-is.
          break;
      }
      
      $element[] = [
        '#theme' => $theme,
        '#path' => $uri,
        '#file_url' => $uri, // here's a good test URI: 'http://techslides.com/demos/sample-videos/small.mp4'
        '#file_mime' => 'video/mp4',
        '#html_id' => 'video-display',
        '#source' => $conf['scitalk_video_source'],
        '#attributes' => [
          'class' => ['scitalk-video', ],
          'data-conversation' => 'none',
          'lang' => 'en',
          'video-attributes' => $video_attributes,
        ],
      ];
      
      //now embed the jwplayer.  Please refer to jw_player.module jw_player_theme() for input parameters
      // $element[] = [
      //   'player' => [
      //     '#type' => 'jw_player',
      //     '#html_id' => 'video_player',
      //     '#file_url' => $uri,
      //     '#file_mime' => 'video/mp4',
      //     '#preset' => $this->getSetting('jwplayer_preset'),  
      //     '#attached' => [   //attaching our js with jwplayer as dependencies allows this to work
      //       'library' => ['scitalk_media/scitalk_jw_js'],
      //     ],
      //     '#settings' => [
      //       'image' => $thumbnail,
      //     ],
      //   ]
      // ];

      //embed video.js player
      $element[] = [
        '#theme' => 'scitalk_media_videojs',
        '#file_url' => $uri,
        '#file_mime' => 'video/mp4',
        // '#items' => [
        //   'uri' => $uri,
        //   'filemime' => 'video/mp4',
        // ],
        '#attached' => [
          'library' => ['scitalk_media/scitalk_video_js'],
        ],
        '#player_attributes' => [
          // 'width' => '854',    //not using these
          // 'height' => '480',
          'loop' => FALSE,
          'preload' => 'auto',     // one of: 'metadata','auto','none'
          'hidecontrols' => FALSE,
          'controls' => TRUE,
          'background' => $thumbnail,
          'aspectRatio' => "16:9",
          'fluid' => TRUE,
          'responsive' => TRUE,
          'playbackRates' => [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2],
          'muted' => FALSE,
        ],
      ];
      
    }
    
    //now check if this is an arxiv media type
    if ($media_entity && (method_exists($media_entity, 'getSource')) && ($source = $media_entity->getSource()) && $source instanceof SciTalkArXiv) {
      $element[] = [
        '#prefix' => '<div></div>',
      ];
    }
                 
    return $element;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getSettings()['target_type'] != 'media') {
      return FALSE;
    }
  
    if (parent::isApplicable($field_definition)) {
      $media_type = current($field_definition->getSettings()['handler_settings']['target_bundles']);
      if ($media_type) {
        $media_type = MediaType::load($media_type);
        return ($media_type && $media_type->getSource() instanceof SciTalkVideo) || ($media_type && $media_type->getSource() instanceof SciTalkArXiv);
      }
    }
    return FALSE;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['uri_field'] = 'none';
    $options['jwplayer_preset'] = '';
    $options['requirejw'] = 0;
    return $options;
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    //need to implement a settings form that allows you to choose which fields on the media entity to use as the URL
    $field_options = [];
    $field_options['none'] = $this->t('None');
    
    
    $fieldSettings = $this->getFieldSettings();
    foreach($fieldSettings['handler_settings']['target_bundles'] as $bundle) {
      $fields_on_bundle = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties([
        'bundle' => $bundle,
      ]);

      foreach($fields_on_bundle as $machine_name => $field_config) {
        $field_options[$machine_name] = $field_config->label();
      }
    }
    
    $form['uri_field'] = [
      '#type' => 'select',
      '#title' => $this->t('URI field'),
      '#options' => $field_options,
      '#description' => $this->t('The field on your SciTalk media type that stores the URI to the video.'),
      '#default_value' => $this->getSetting('uri_field'),
    ];
    
    
    //determine if we should be even showing the jw player options as this is only for video types
    //best thing to do is simply show a checkbox with the presets for jw being shown only when checked.
    $form['requirejw'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use JW Player to show media?'),
      '#description' => $this->t('When checked, this signals the formatter to use JW Player to show the media referenced in the URI Field.'),
      '#default_value' =>  boolval($this->getSetting('requirejw')),
      '#attributes' => ['class' => ['requirejw']],
    ];
    
    $presets = Jw_player::loadMultiple();
    $options = [];
    if (!empty($presets)) {
      foreach ($presets as $type => $type_info) {
        $options[$type] = $type_info->label();
      }
      $form['jwplayer_preset'] = [
        '#title' => t('Select preset'),
        '#type' => 'select',
        '#empty_option' => t('- No preset selected -'),
        '#default_value' => $this->getSetting('jwplayer_preset') ?: 'none',
        '#options' => $options,
        '#states' => array(
          'visible' => array(
            '.requirejw' => array('checked' => TRUE),
          ),
        ),
      ];
    }
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $field_options = [];
    
    $fieldSettings = $this->getFieldSettings();
    foreach($fieldSettings['handler_settings']['target_bundles'] as $bundle) {
      $fields_on_bundle = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties([
        'bundle' => $bundle,
      ]);
      
      foreach($fields_on_bundle as $machine_name => $field_config) {
        $field_options[$machine_name] = $field_config->label();
      }
    }
    
    
    if($this->getSetting('uri_field') == 'none') {
      $summary[] = $this->t('There is no URI field set! The output of this field will be a placeholder.');
    }
    else {
      $field_label = $field_options[$this->getSetting('uri_field')];
      
      if($field_label != '') {
        $summary[] = $this->t('URI field: ') . $field_label;
      }
      else {
        $summary[] = $this->t('There is no field label for the URI field! The machine name is ') . $this->getSetting('uri_field');
      }
    }
    
    if($this->getSetting('requirejw') == 1) {
      if($this->getSetting('jwplayer_preset') == '') {
        $summary[] = $this->t('No JW Player preset chosen. Default presets will be used.');
      }
      else {
        $presets = Jw_player::loadMultiple();
        $options = [];
        if (!empty($presets)) {
          foreach ($presets as $type => $type_info) {
            $options[$type] = $type_info->label();
          }
        }
        $summary[] = $this->t('JW Player Preset: ') . $options[$this->getSetting('jwplayer_preset')];
      }
    }
    
    return $summary;
  }
  
}
