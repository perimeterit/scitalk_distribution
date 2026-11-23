<?php

namespace Drupal\scitalk_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\File\FileExists;

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
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Not overriding anything here, so we'll just call the parent.
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

    switch ($thumb_to_use) {
      // Use the field_talk_thumbnail field for the thumb.
      case 'field':
        $field = $node->get('field_talk_thumbnail')->getValue();
        if (isset($field[0])) {
          $file = File::load($field[0]['target_id']);

          $element = [
            '#theme' => 'image_style',
            '#style_name' => $style_to_use,
            '#uri' => $file->getFileUri(),
          ];
        }
        break;

      case 'media':
        $video_field = $node->get('field_talk_video')->getValue();
        if (!empty($video_field[0])) {
          $media_entity = \Drupal::entityTypeManager()->getStorage('media')->load($video_field[0]['target_id']);
        }

        $thumbnail_uri = '';
        if (!empty($media_entity)) {
          // Get the thumbnail attached to the video.
          $file = File::load($media_entity->thumbnail->target_id);
          $media_thumb = $file->getFileUri() ?? '';

          // If the file exists then use it as the thumbnail otherwise use the default.
          if (!\Drupal::service('file_system')->getDestinationFilename($media_thumb, FileExists::Error)) {
            $thumbnail_uri = $media_thumb;
          }
        }

        break;
    }

    // If no thumbnail is set, use the default. It is copied into the media
    // icons directory on install so it can be rendered through the image style.
    if (empty($thumbnail_uri)) {
      $default_uri = \Drupal::config('media.settings')->get('icon_base_uri') . '/scitalk-default-thumbnail.png';
      if (file_exists($default_uri)) {
        return [
          '#theme' => 'image_style',
          '#style_name' => $style_to_use,
          '#uri' => $default_uri,
          '#alt' => '',
        ];
      }

      // Fall back to the module copy if the icon hasn't been copied yet.
      $modulePath = \Drupal::service('extension.path.resolver')
        ->getPath('module', 'scitalk_media');
      return [
        '#theme' => 'image',
        '#uri' => '/' . $modulePath . '/images/icons/scitalk-default-thumbnail.png',
        '#alt' => '',
      ];
    }

    return [
      '#theme' => 'image_style',
      '#style_name' => $style_to_use,
      '#uri' => $thumbnail_uri,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Use the 'talk' content type for the talk thumbnail.
    if ($field_definition->getTargetBundle() == 'talk' && $field_definition->getName() == 'field_talk_thumbnail') {
      return TRUE;
    }

    return FALSE;
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
    $form['which_thumb'] = [
      '#title' => $this->t('Thumbnail source to use'),
      '#type' => 'select',
      '#options' => [
        'field' => $this->t('Use thumbnail field'),
        'media' => $this->t('Use first SciTalk Media thumbnail'),
      ],
      '#default_value' => $chosen ?? 'field',
    ];

    $chosen = $this->getSetting('which_style');

    $styles = ImageStyle::loadMultiple();
    $available_styles = [];
    foreach ($styles as $key => $obj) {
      $available_styles[$key] = $obj->label();
    }
    $form['which_style'] = [
      '#title' => $this->t('Choose a Style'),
      '#type' => 'select',
      '#options' => $available_styles,
      '#default_value' => $chosen ?? 'thumbnail',
    ];

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
