<?php

/**
 * @file
 * Definition of Drupal\scitalk_media\Plugin\views\field\SciTalkMediaThumbnail
 */

namespace Drupal\scitalk_media\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\scitalk_media\Plugin\media\Source\SciTalkVideo;

/**
 * Field handler to reference the right thumbnail when using views on a media enabled content type
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("scitalk_media_thumbnail")
 */
class SciTalkMediaThumbnail extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // no Query to be done.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['which_thumb'] = ['default' => 'field'];  //options are field or media
    $options['which_style'] = ['default' => 'thumbnail'];  //options are derived from system image styles
    
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $form['which_thumb'] = array(
      '#title' => $this->t('Thumbnail source to use'),
      '#type' => 'select',
      '#options' => [ 'field' => $this->t('Use thumbnail field'),
                      'media' => $this->t('Use first SciTalk Media thumbnail'),], 
      '#default_value' => isset($this->options['which_thumb']) ? $this->options['which_thumb'] : '',
    );
    
    $chosen = $this->options['which_style'];
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
    
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    global $base_url;
//xdebug_break();
    $item = $values->_entity;
    
    
    $thumb_to_use = isset($this->options['which_thumb']) ? $this->options['which_thumb'] : '';
    $style_to_use = isset($this->options['which_style']) ? $this->options['which_style'] : 'thumbnail';
    
    //field_talk_thumbnail  is the field on the talk that stores an image for the thumbnail.
    
    //field_talk_video is the scitalk video field.
    
    //
    
    //prefill the output render to the no-image option
    
    $render = [];
    
    switch($thumb_to_use) {
      case 'field':  //use the field_talk_thumbnail field for the thumb
        //this is an image field.  Let's display it via the display mechanisms available.
        $field = $item->get('field_talk_thumbnail')->getValue();
        if(isset($field[0])) {
          $file = File::load($field[0]['target_id']);
          //$image_uri = ImageStyle::load('thumbnail')->buildUrl($file->getFileUri());
          $render = [
            '#theme' => 'image_style',
            '#style_name' => $style_to_use,
            '#uri' => $file->getFileUri(),
          ];
        }
        break;
        
        
      case 'media':  //use the field_talk_video field, find the first instance of a ScitalkVideo thumb
        $field = $item->get('field_talk_video')->getValue();
        $media_entity = NULL;
        if(isset($field[0])) {
          //we'll pick off the first one and use that as our thumbnail.
          foreach($field as $key => $arr) {
            //$media_entity = entity_load('media', $field[$key]['target_id']);
            $media_entity = \Drupal::entityTypeManager()->getStorage('media')->load($field[$key]['target_id']);
            if ($media_entity && (method_exists($media_entity, 'getSource')) && ($source = $media_entity->getSource()) && $source instanceof SciTalkVideo) {  //only care if this is the SciTalk media type
              break;
            }
          }
        }
        
        if($media_entity) {
          $default_thumbnail_filename = $media_entity->getSource()->getPluginDefinition()['default_thumbnail_filename'];
          $thumbnail_uri = \Drupal::service('config.factory')->get('media.settings')->get('icon_base_uri') . '/' . $default_thumbnail_filename;
        }
        $render = [
          '#theme' => 'image_style',
          '#style_name' => $style_to_use,
          '#uri' => $thumbnail_uri,
        ];
        break;
        
      default:  //no output.  Show broken thumb image?
        
        break;
        
    }
    
    

  return $render;

  }
}