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
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;



/**
 * Plugin implementation of the 'scitalk_thumbnail_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "scitalk_thumbnail_formatter",
 *   label = @Translation("SciTalk Thumbnail Formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SciTalkThumbnailFormatter extends FormatterBase {

  
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
    $node = $items->getEntity();
    $thumb_to_use = $this->getSetting('which_thumb');
    $style_to_use = $this->getSetting('which_style');
    switch($thumb_to_use) {
      case 'field':  //use the field_talk_thumbnail field for the thumb
        //this is an image field.  Let's display it via the display mechanisms available.
        $field = $node->get('field_talk_thumbnail')->getValue();
        if(isset($field[0])) {
          $file = File::load($field[0]['target_id']);
          //$image_uri = ImageStyle::load('thumbnail')->buildUrl($file->getFileUri());
          $element = [
            '#theme' => 'image_style',
            '#style_name' => 'thumbnail',
            '#uri' => $file->getFileUri(),
          ];
        }
        break;
        
        
      case 'media':  //use the field_talk_video field, find the first instance of a ScitalkVideo thumb
        $field = $node->get('field_talk_video')->getValue();
        $media_entity = NULL;
        if(isset($field[0])) {
          //we'll pick off the first one and use that as our thumbnail.
          foreach($field as $key => $arr) {
            $media_entity = entity_load('media', $field[$key]['target_id']);
            if ($media_entity && (method_exists($media_entity, 'getSource')) && ($source = $media_entity->getSource()) && $source instanceof SciTalkVideo) {  //only care if this is the SciTalk media type
              break;
            }
          }
        }

	$thumbnail_uri = '';
        if($media_entity) {
          $default_thumbnail_filename = $media_entity->getSource()->getPluginDefinition()['default_thumbnail_filename'];
          $thumbnail_uri = \Drupal::service('config.factory')->get('media.settings')->get('icon_base_uri') . '/' . $default_thumbnail_filename;
        }
        $element = [
          '#theme' => 'image_style',
          '#style_name' => $style_to_use,
          '#uri' => $thumbnail_uri,
        ];
        break;
        
      default:  //no output.  Show broken thumb image?
        
        break;
        
    }
    return $element;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    
    //we only target the 'talk' content type for the talk thumbnail.
    if($field_definition->getTargetBundle() == 'talk' &&  $field_definition->getName() == 'field_talk_thumbnail') {
      return TRUE;    
    }
   
  }
  
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['which_thumb'] = 'field';
    $options['which_style'] = 'thumbnail';
    
    return $options;
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    
    $chosen = $this->getSetting('which_thumb');
    $form['which_thumb'] = array(
      '#title' => $this->t('Thumbnail source to use'),
      '#type' => 'select',
      '#options' => [ 'field' => $this->t('Use thumbnail field'),
        'media' => $this->t('Use first SciTalk Media thumbnail'),],
      '#default_value' => isset($chosen) ? $chosen : 'field',
    );
    
    $chosen = $this->getSetting('which_style');

    $styles = ImageStyle::loadMultiple();
    $available_styles = [];
    foreach($styles as $key => $obj) {
      $available_styles[$key] = $obj->label();
    }
    $form['which_style'] = array(
      '#title' => $this->t('Choose a Style'),
      '#type' => 'select',
      '#options' => $available_styles,
      '#default_value' => isset($chosen) ? $chosen : 'thumbnail',
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $options = [ 
      'field' => $this->t('Use thumbnail field'),
      'media' => $this->t('Use first SciTalk Media thumbnail'),
    ];
    
    $which_thumb = $this->getSetting('which_thumb');
    $summary[] = $this->t('Thumbnail setting') . ':' . $options[$which_thumb];
   
    $which_style = $this->getSetting('which_style');
    $summary[] = $this->t('Style setting') . ':' . $which_style;
    return $summary;
  }
  
}
