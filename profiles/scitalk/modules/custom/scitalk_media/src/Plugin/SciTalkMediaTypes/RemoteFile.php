<?php

namespace Drupal\scitalk_media\Plugin\SciTalkMediaTypes;

use Drupal\scitalk_media\SciTalkMediaPluginBase;


/**
 * PIRSA remote file Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this media type.  
 *
 * @Plugin(
 *   id = "SciTalkRemoteFile",
 *   description = @Translation("Remote file plugin for handling links to remote files."),
 *   media_type = "scitalk_remote_file",
 *   media_source = "",
 * )
 */
class RemoteFile extends SciTalkMediaPluginBase {
   
  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'SciTalkRemoteFile';
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
    $remote_video = $this->entity->{$configuration['source_field']}->getString();
    
    $this->entity->field_media_scitalk_remote_file = $remote_video;
    $this->entity->save();
  }
}
