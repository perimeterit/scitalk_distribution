<?php

namespace Drupal\jw_player;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Example entity.
 */
interface Jw_playerInterface extends ConfigEntityInterface {

  /**
   * Returns the preset settings.
   *
   * @return array
   *   The preset settings.
   */
  public function getSettings();

  /**
   * Returns a preset setting value for a specific key.
   *
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will return $values[$key]. If $key is an array, each element
   *   of the array will be used as a nested key. If $key = array('foo', 'bar')
   *   it will return $values['foo']['bar'].
   * @param mixed $default
   *   (optional) The default value if the specified key does not exist.
   *
   * @return mixed
   *   The value for the given key, or NULL.
   */
  public function getSetting($key, $default = NULL);

  /**
   * Helper function to display JW Player preset settings.
   *
   * @param string $format (optional)
   *   Whether the preset settings are returned as an 'array' or formatted 'string'.
   *
   * @return array|string
   *   The preset settings stored in an array or as an imploded string for rendering.
   */
  public function settingsDisplay($format = 'string');
}
