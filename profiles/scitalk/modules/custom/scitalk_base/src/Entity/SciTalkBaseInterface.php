<?php

namespace Drupal\scitalk_base\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining SciTalk Base entities.
 */
interface SciTalkBaseInterface extends ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.

  public function getSiteVocabulary();
  public function setSiteVocabulary($vocabulary);
  public function getSciVideosVocabulary();
  public function setSciVideosVocabulary($vocabulary);
  public function getTermMappings();
  public function setTermMappings($mappings);
}
