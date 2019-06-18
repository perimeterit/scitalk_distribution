<?php

namespace Drupal\scitalk_media;


use Drupal\scitalk_media\SciTalkMediaTypesInterface;
use Drupal\Core\Plugin\PluginBase;

class SciTalkMediaPluginBase extends PluginBase implements SciTalkMediaTypesInterface {
  /**
   *
   * @var \Drupal\Core\Entity\EntityInterface entity
   */
  protected $entity = NULL;
  
  function __construct($configuration = NULL) {
    if(is_array($configuration)) {
      $this->entity = $configuration[0];
    }
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return '';
  }
  
  /**
   * entityInsert  base method that must be overridden in order to manage any entity inserts.
   * {@inheritDoc}
   * @see \Drupal\scitalk_media\ScitalkMediaTypesInterface::entityInsert()
   */
  public function entityInsert() {
  }
  
  
  /**
   * entityMetaDataUpdate  base method that must be overridden in order to manage metadata updates
   * {@inheritDoc}
   * @see \Drupal\scitalk_media\SciTalkMediaTypesInterface::entityMetaDataUpdate()
   */
  public function entityMetaDataUpdate() {
  }
  
}