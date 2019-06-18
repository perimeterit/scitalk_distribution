<?php

namespace Drupal\jw_player;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of presets.
 */
class Jw_playerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['description'] = $this->t('Description');
    $header['settings'] = $this->t('Settings');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['description'] = $entity->description;
    $preset_settings = $entity->settingsDisplay('array');
    unset($preset_settings['name']);
    unset($preset_settings['description']);
    $settings = implode('<br />', $preset_settings);
    $row['settings'] = t($settings);
    return $row + parent::buildRow($entity);
  }

}
