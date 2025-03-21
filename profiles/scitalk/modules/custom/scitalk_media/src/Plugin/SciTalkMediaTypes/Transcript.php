<?php

namespace Drupal\scitalk_media\Plugin\SciTalkMediaTypes;

use Drupal\scitalk_media\SciTalkMediaPluginBase;


/**
 * SciTalk Transcript Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this media type.  
 *
 * @Plugin(
 *   id = "SciTalkTranscript",
 *   description = @Translation("The Transcript plugin for handling various SciTalk Media type functions."),
 *   media_type = "scitalk_transcript",
 *   media_source = "",
 * )
 */
class Transcript extends SciTalkMediaPluginBase {
   
  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'SciTalkTranscript';
  }

  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\scitalk_media\SciTalkMediaPluginBase::entityInsert()
   */
  public function entityInsert() {
    $this->entityMetaDataUpdate();
  }
  
  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\scitalk_media\SciTalkMediaPluginBase::entityMetaDataUpdate()
   */
  public function entityMetaDataUpdate() {
    $source = $this->entity->bundle->entity->getSource();
    $configuration = $source->getConfiguration();
    // $val = $this->entity->{$configuration['source_field']}->getString();
    $val = $this->entity->{$configuration['source_field']}->getValue();

    $this->entity->field_media_scitalk_transcript = $val;
    $this->entity->save();
  }
  
}
