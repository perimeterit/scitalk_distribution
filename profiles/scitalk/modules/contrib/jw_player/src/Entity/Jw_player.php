<?php

namespace Drupal\jw_player\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\jw_player\Jw_playerInterface;
/**
 * Defines the JW Player preset entity.
 *
 * @ConfigEntityType(
 *   id = "jw_player",
 *   label = @Translation("JW Player preset"),
 *   handlers = {
 *     "list_builder" = "Drupal\jw_player\Jw_playerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jw_player\Form\JwplayerPresetAdd",
 *       "edit" = "Drupal\jw_player\Form\JwplayerPresetAdd",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "preset",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "status",
 *     "description",
 *     "settings",
 *   },
 *   links = {
 *    "collection" = "/admin/config/media/jw_player",
 *    "edit-form" = "/admin/config/media/jw_player/{jw_player}",
 *    "delete-form" = "/admin/config/media/jw_player/{jw_player}/delete"
 *   }
 * )
 */
class Jw_player extends ConfigEntityBase implements Jw_playerInterface {

  /**
   * The ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Label.
   *
   * @var string
   */
  public $label;

  /**
   * Description.
   *
   * @var string
   */
  public $description;

  public $settings = array();

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    if (isset($this->settings['responsive']) && $this->settings['responsive']) {
      unset($this->settings['height']);
      $this->settings['width'] .= '%';
    }
    else {
      unset($this->settings['aspectratio']);
    }
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default = NULL) {
    $exists = NULL;
    $value = &NestedArray::getValue($this->settings, (array) $key, $exists);
    if (!$exists) {
      $value = $default;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsDisplay($format = 'string') {
    $summary = [];
    $preset_settings = $this->getSettings();
    // Name and description.
    $summary['name'] = t('Preset: @name', ['@name' => $this->label()]);
    if (!empty($this->description)) {
      $summary['description'] = t('Description: @desc', ['@desc' => $this->description]);
    }
    // Skin.
    if (!isset($preset_settings['preset_source']) || $preset_settings['preset_source'] == 'drupal') {
      $summary['source'] = t('Preset source: Drupal');
      if (!empty($preset_settings['skin'])) {
        $skin_label = $preset_settings['skin'];
        $summary['skin'] = t('Skin: @skin', ['@skin' => $skin_label]);
      }
      // Dimensions and stretching.
      if (isset($preset_settings['stretching'])) {
        switch ($preset_settings['stretching']) {
          case 'exactfit':
            $stretch = 'exact fit';
            break;
          case 'uniform':
            $stretch = 'uniform';
            break;
          case 'fill':
            $stretch = 'fill';
            break;
          default:
            $stretch = '';
            break;
        }
      }
      if (!empty($stretch)) {
        if (!empty($preset_settings['responsive'])) {
          $summary['dimensions'] = t('Dimensions: @width width (@aspect_ratio), @stretch', [
            '@width' => $preset_settings['width'],
            '@aspect_ratio' => $preset_settings['aspectratio'],
            '@stretch' => $stretch,
          ]);
        }
        else {
          $summary['dimensions'] = t('Dimensions: @widthx@height, @stretch', [
            '@width' => $preset_settings['width'],
            '@height' => $preset_settings['height'],
            '@stretch' => $stretch,
          ]);
        }
      }
      else {
        if (!empty($preset_settings['responsive'])) {
          $summary['dimensions'] = t('Dimensions: @width width (@aspect_ratio)', [
            '@width' => $preset_settings['width'],
            '@aspect_ratio' => $preset_settings['aspectratio'],
          ]);
        }
        else {
          $summary['dimensions'] = t('Dimensions: @widthx@height', [
            '@width' => $preset_settings['width'],
            '@height' => $preset_settings['height'],
          ]);
        }
      }
      // Enabled options.
      $enabled = [];
      if (!empty($preset_settings['autostart'])) {
        $enabled[] = t('Autostart');
      }
      if (!empty($preset_settings['mute'])) {
        $enabled[] = t('Mute');
      }
      if (isset($preset_settings['sharing']) && $preset_settings['sharing']) {
        $enabled[] = t('Sharing');
      }
      if (!empty($enabled)) {
        $enabled_string = implode(', ', $enabled);
        $summary['enabled'] = t('Enabled options: @enabled', ['@enabled' => $enabled_string]);
      }
      // Sharing sites.
      $sharing_weights = NULL;
      if (isset($preset_settings['sharing_sites']['sites'])) {
        foreach ($preset_settings['sharing_sites']['sites'] as $key => $value) {
          if ($value['enabled'] == TRUE) {
            $sharing_weights[$key] = $value['weight'];
          }
        }
        if ($sharing_weights) {
          asort($sharing_weights);
          $sharing_sites = jw_player_sharing_sites();
          $sharing_sorted = array_intersect_key($sharing_sites, $sharing_weights);

          $sharing_sorted_string = implode(', ', $sharing_sorted);
          $summary['sharing'] = t('Sharing sites: @sharing', ['@sharing' => $sharing_sorted_string]);
        }
      }
    }
    else {
      $summary['source'] = t('Preset source: JW Player');
    }
    return ($format == 'string') ? implode('<br />', $summary) : $summary;
  }
}
