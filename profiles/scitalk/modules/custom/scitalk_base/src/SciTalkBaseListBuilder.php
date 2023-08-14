<?php

namespace Drupal\scitalk_base;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of SciTalk Base entities.
 */
class SciTalkBaseListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Mapping name');
    $header['id'] = $this->t('Machine name');
    $header['map_from'] = $this->t('Local vocabulary type');
    $header['map_to'] = $this->t('SciVideos vocabulary type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['map_from'] = $entity->getSiteVocabulary();
    $row['map_to'] = $entity->getSciVideosVocabulary();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $url = Url::fromRoute('scitalk_base.scitalk_base_fields_mapping', ['mapping_type' => $entity->id()]);

    //change Edit title and add Field Mappings operation at the begining
    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Settings');
      $operations['field_mappings'] = [
        'title' => $this->t('Terms Mapping'),
        'url' => $url,
        'weight' => -1000,
      ];
    }
 
    return $operations;
  }

}
