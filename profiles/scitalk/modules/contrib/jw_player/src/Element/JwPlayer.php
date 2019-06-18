<?php

namespace Drupal\jw_player\Element;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\jw_player\Entity\Jw_player;

/**
 * Provides a render element for a table.
 *
 * Properties:
 * - #file_url: The URL to the file that should be displayed.
 * - #preset: (optional) Jw Player preset, if not given, uses default settings.
 * - #html_id: (optional) An HTML ID, a random one is generated if not provided.
 *
 * Usage example:
 * @code
 * $build['player'] = array(
 *   '#type' => 'jw_player',
 *   '#preset' => 'example',
 *   '#file_url' => 'public://video.mp4',
 *   '#html_id' => 'example-id',
 * );
 * @endcode
 *
 * @FormElement("jw_player")
 */
class JwPlayer extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#theme' => 'jw_player',
      '#pre_render' => [
        [$class, 'preRenderPlayer'],
      ],
      '#settings' => [],
      '#attached' => [
        'library' => ['jw_player/behaviors'],
      ],
    ];
  }

  /**
   * #pre_render callback for #type 'jw_player'.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   table element.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderPlayer($element) {
    $config = \Drupal::config('jw_player.settings');

    $settings = $element['#settings'];

    if (!isset($element['#html_id'])) {
      // Give each instance of the player a unique id. A random hash is
      // used in place of drupal_html_id() due to potentially conflicting
      // ids in cases where the entire output of the theme function is
      // cached. Prefix with jwplayer, as ID's that start with a number
      // are not valid.
      $element['#html_id'] = 'jwplayer-' . md5(rand());
    }

    // Create a configuration array which will be passed to JWPlayer's
    // JavaScript.
    $settings['file'] = $element['#file_url'];

    if (!empty($element['#preset'])) {
      $preset = Jw_player::load($element['#preset']);
      // Additional check to ensure that the preset has actually loaded. This
      // prevents problems where a preset has been deleted but a field is still
      // configured to use it.
      if (!empty($preset)) {
        // Don't apply preset or default config in case of cloud hosted players.
        if (!$config->get('cloud_player_library_url')) {
          // Merge in the preset settings.
          $settings += $preset->getSettings();
        }

        $cacheability_metadata = CacheableMetadata::createFromRenderArray($element);
        $cacheability_metadata->addCacheableDependency($preset);
        $cacheability_metadata->addCacheableDependency($config);
        $cacheability_metadata->applyTo($element);
      }
    }

    if (!$config->get('cloud_player_library_url')) {
      $settings += static::getDefaultSettings();

      if (isset($settings['mode'])) {
        $settings['primary'] = $settings['mode'];
        unset($settings['mode']);
      }

      if (isset($settings['controlbar']) && !$settings['controlbar']) {
        unset($settings['controlbar']);
      }
    }

    // Unset advertising if is not set.
    if (empty($settings['advertising']['client'])) {
      unset($settings['advertising']);
    }
    else {
      // Add the add break pre roll to schedule if set.
      if (!empty($settings['advertising']['tag'])) {
        // Add the add break pre roll to schedule if set.
        $settings['advertising']['schedule']['adbreak-preroll'] = [
          'tag' => $settings['advertising']['tag'],
          'offset' => 'pre',
        ];
        unset($settings['advertising']['tag']);
      }
      // Add the add break post roll to schedule if set.
      if (!empty($settings['advertising']['tag_post'])) {
        $settings['advertising']['schedule']['adbreak-postroll'] = [
          'tag' => $settings['advertising']['tag_post'],
          'offset' => 'post',
        ];
      }
      unset($settings['advertising']['tag_post']);
    }

    // Unset sharing if it is not set.
    if (isset($settings['sharing']) && $settings['sharing']) {
      unset($settings['sharing']);
      $settings['sharing']['sites'] = [];
      foreach($settings['sharing_sites']['sites'] as $key => $value) {
        if ($value['enabled'] == 1) {
          $settings['sharing']['sites'][] = $key;
        }
      }
      // If none selected, all selected.
      if (!$settings['sharing']['sites']) {
        foreach($settings['sharing_sites']['sites'] as $key => $value) {
          $settings['sharing']['sites'][] = $key;
        }
      }
      $settings['sharing']['heading'] = $settings['sharing_heading'];
    }
    unset($settings['sharing_sites']);
    unset($settings['sharing_heading']);

    // Add the build settings to drupal settings.
    $element['#attached']['drupalSettings']['jw_player']['players'][$element['#html_id']] = $settings;

    // Add the license_key if provided.
    if ($config->get('jw_player_key')) {
      $element['#attached']['drupalSettings']['jw_player']['license_key'] = $config->get('jw_player_key');
    }

    return $element;

  }

  /**
   * Default JW PLayer settings.
   *
   * @return array
   *    Returns the default settings for JW Player. Used in cases where a preset
   *    is not provided when the JW Player theme function is called.
   */
  public static function getDefaultSettings() {
    $defaults = array(
      'width' => '640',
      'height' => '480',
      'mode' => 'html5',
      'autostart' => FALSE,
      'controlbar' => 'bottom',
      'advertising' => array(
        'client' => '',
        'tag' => '',
      ),
    );

    $library_discovery = \Drupal::service('library.discovery');
    $library = $library_discovery->getLibraryByName('jw_player', 'jwplayer');

    if (!empty($library['library path'])) {
      $defaults['base'] = file_create_url($library['library path'] . '/');
      // JW Player 7+ no longer uses the base for the flash path but supports
      // an explicit configuration option for it again.
      $defaults['flashplayer'] = $defaults['base'] . 'jwplayer.flash.swf';
    }
    return $defaults;
  }

}
