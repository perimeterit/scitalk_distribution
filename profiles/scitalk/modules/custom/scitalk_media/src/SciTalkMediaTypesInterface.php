<?php 

namespace Drupal\scitalk_media;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Form\FormStateInterface;

//TODO: what are our methods we require each plugin to do?  Must have the prototype added here
interface SciTalkMediaTypesInterface extends PluginInspectionInterface {
  

  
  /**
   * entityInsert 
   * Plugin support for managing a SciTalk media entity's insertion 
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  function entityInsert();
  
  /**
   * entityMetaDataUpdate
   * Plugin support for managing a SciTalk media entity's update of meta data
   */
  function entityMetaDataUpdate();
}
  