<?php

namespace Drupal\jw_player\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\jw_player\Entity\Jw_player;

/**
 * Plugin implementation of the 'foo_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "jwplayer_formatter",
 *   label = @Translation("Jw player"),
 *   field_types = {
 *     "file",
 *     "video",
 *     "link",
 *   },
 * )
 */
class JwplayerFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'jwplayer_preset' => NULL,
      'preview_image_field' => NULL,
      'preview_image_style' => NULL,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $presets = Jw_player::loadMultiple();
    $options = [];
    if (!empty($presets)) {
      foreach ($presets as $type => $type_info) {
        $options[$type] = $type_info->label();
      }
      $element['jwplayer_preset'] = [
        '#title' => t('Select preset'),
        '#type' => 'select',
        '#empty_option' => t('- No preset selected -'),
        '#default_value' => $this->getSetting('jwplayer_preset') ?: 'none',
        '#options' => $options,
      ];
      $element['links'] = [
        '#theme' => 'links',
        '#links' => [
          [
            'url' => Url::fromRoute('jw_player.preset_add'),
            'title' => $this->t('Create new preset'),
          ],
          [
            'url' => Url::fromRoute('entity.jw_player.collection'),
            'title' => $this->t('Manage presets'),
          ],
        ],
      ];

      if ($this->getSetting('jwplayer_preset') && $this->getSetting('jwplayer_preset') != 'none') {
        $element['links']['#links'][] = [
          'url' => Url::fromRoute('entity.jw_player.edit_form', ['jw_player' => $this->getSetting('jwplayer_preset')]),
          'title' => t('Manage selected preset'),
        ];
      }

      // Add support for configurable preview images.
      if (\Drupal::moduleHandler()->moduleExists('image') && $this->fieldDefinition->getTargetEntityTypeId() && $this->fieldDefinition->getTargetBundle()) {
        $options = [];
        $field_definitions = \Drupal::service('entity_field.manager')
          ->getFieldDefinitions($this->fieldDefinition->getTargetEntityTypeId(), $this->fieldDefinition->getTargetBundle());
        // @todo add support for fields on file references.

        foreach ($field_definitions as $field_name => $field_definition) {
          if ($field_definition->getType() == 'image') {
            // Structure of the key can be used later on in the formatter's view
            // callback in order to fetch the image uri from the configure field.
            $options[$this->fieldDefinition->getTargetEntityTypeId() . ':' . $this->fieldDefinition->getTargetBundle() . '|' . $field_name] = $field_definition->getLabel() . ' (' . $this->fieldDefinition->getTargetEntityTypeId() . ':' . $this->fieldDefinition->getTargetBundle() . ')';
          }
        }

        if ($options) {
          $element['preview_image_field'] = [
            '#title' => t('Preview image source'),
            '#description' => t('You can choose an image field directly on this node type, or on any entity of an entity/file/term reference field on this content type.'),
            '#type' => 'select',
            '#options' => $options,
            '#default_value' => $this->getSetting('preview_image_field') ? $this->getSetting('preview_image_field') : '',
            '#empty_option' => t('None'),
          ];

          $options = image_style_options();
          $element['preview_image_style'] = [
            '#title' => t('Preview image style'),
            '#description' => t('Choose an image style that will be used for the preview image.'),
            '#type' => 'select',
            '#options' => $options,
            '#default_value' => $this->getSetting('preview_image_style') ? $this->getSetting('preview_image_style') : '',
            '#states' => [
              'invisible' => [
                array(':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][preview_image_field]"]' => ['value' => '']),
              ],
            ],
          ];
        }
      }
    }
    else {
      $element['no_preset_message'] = [
        '#markup' => '<div class="messages warning">' . t('No presets are available. Please <a href="@create">create a preset</a> in order to proceed.', ['@create' => Url::fromRoute('jw_player.preset_add')->toString()]) . '</div>',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];
    if (isset($settings['jwplayer_preset'])) {
      $preset = $this->loadPreset();
      if (!$preset) {
        return $summary;
      }
      $preset_settings = $preset->settingsDisplay('array');
      // Formatted preset name and player type.
      $summary[] = $preset_settings['name'];
      if (stripos($preset_settings['source'], 'drupal') !== FALSE) {
        // Skin, dimensions, enabled options, and sharing sites.
        if (isset($preset_settings['skin'])) {
          $summary[] = $preset_settings['skin'];
        }
        $summary[] = $preset_settings['dimensions'];
        if (isset($preset_settings['enabled'])) {
          $summary[] = $preset_settings['enabled'];
        }
        if (isset($preset_settings['sharing'])) {
          $summary[] = $preset_settings['sharing'];
        }
      }
      else {
        $summary[] = $preset_settings['source'];
      }

      // Preview image settings.
      if (isset($settings['preview_image_field']) && !empty($settings['preview_image_field'])) {
        // Get image field label.
        $split = explode('|', $settings['preview_image_field']);
        $field_definitions = \Drupal::service('entity_field.manager')
          ->getFieldDefinitions($this->fieldDefinition->getTargetEntityTypeId(), $this->fieldDefinition->getTargetBundle());
        $info = $field_definitions[$split[1]];
        // Get image style label.
        if (!empty($settings['preview_image_style'])) {
          $style = ImageStyle::load($settings['preview_image_style']);
          $preview_image_style = $style->label();
        }
        else {
          $preview_image_style = 'Original';
        }
        $summary[] = t('Preview: @field (@style)', array(
          '@field' => $info->label(),
          '@style' => $preview_image_style,
        ));
      }
    }
    else {
      $summary[] = t('No preset selected');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $settings = $this->getSettings();
    // Prepare preview image.
    $image_style = NULL;
    $image_url = NULL;
    $cache_tags = [];
    if ($settings['preview_image_field']) {
      $split = explode('|', $settings['preview_image_field']);
      $preview_image_field = $split[1];
      if ($preview_items = $items->getEntity()->get($preview_image_field)) {
        if ($image_style = ImageStyle::load($settings['preview_image_style'])) {
          if ($image = $items->getEntity()->{$preview_image_field}->entity) {
            $image_url = $image_style->buildUrl($image->getFileUri());
            $cache_tags = $image_style->getCacheTags();
          }
        }
      }
    }
    // Process files for the theme function.
    foreach ($items as $delta => $item) {
      if ($item->entity) {
        $file_uri = $item->entity->getFileUri();
        $file_mime = $item->entity->getMimeType();
        $uri = file_create_url($file_uri);

        // Add cache tags for the referenced file and the preset if it can be
        // loaded, to prevent fatal errors.
        $tags = Cache::mergeTags($cache_tags, $item->entity->getCacheTags());
      }
      // Allow for formatting of Link field.
      elseif ($this->fieldDefinition->getType() === 'link') {
        $uri = $item->uri;
        $file_mime = FALSE;
        $tags = [];
      }
      else {
        continue;
      }

      if ($preset = $this->loadPreset()) {
        $tags = Cache::mergeTags($tags, $preset->getCacheTags());
      }
      $element[$delta] = [
        'player' => [
          '#type' => 'jw_player',
          '#file' => $item->entity,
          '#file_url' => $uri,
          '#file_mime' => $file_mime,
          '#item' => $item,
          '#preset' => $this->getSetting('jwplayer_preset'),
          // Give each instance of the player a unique id. A random hash is
          // used in place of drupal_html_id() due to potentially conflicting
          // ids in cases where the entire output of the theme function is
          // cached. Prefix with jwplayer, as ID's that start with a number
          // are not valid.
          '#html_id' => 'jwplayer-' . md5(rand()),
        ],
        '#attached' => [
          'library' => ['jw_player/jwplayer'],
        ],
        '#cache' => [
          'tags' => $tags,
        ],
      ];
      // Add preview image.
      if ($image_url) {
        $element[$delta]['player']['#settings']['image'] = $image_url;
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($preset = $this->loadPreset()) {
      $dependencies['config'][] = $preset->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * Loads the configured preset.
   *
   * @returns \Drupal\jw_player\Entity\Jw_player
   *   The preset specified in the formatter settings.
   */
  protected function loadPreset() {
    if ($id = $this->getSetting('jwplayer_preset')) {
      return Jw_player::load($id);
    }
    return NULL;
  }

}
