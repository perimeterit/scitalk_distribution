<?php

namespace Drupal\scitalk_media\Plugin\SciTalkMediaTypes;

use Drupal\scitalk_media\SciTalkMediaPluginBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileExists;


/**
 * SciTalk SlideShow item Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this media type.  
 *
 * @Plugin(
 *   id = "SciTalkSlideshowItem",
 *   description = @Translation("SlideShow item plugin."),
 *   media_type = "scitalk_slideshow_item",
 *   media_source = "",
 * )
 */
class SlideshowItem extends SciTalkMediaPluginBase {

    /**
     * {@inheritDoc}
     * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
     */
    public function getPluginId() {
        return 'SciTalkSlideshowItem';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Drupal\scitalk_media\SciTalkMediaPluginBase::entityInsert()
     */
    public function entityInsert() {
        return $this->entityMetaDataUpdate();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Drupal\scitalk_media\SciTalkMediaPluginBase::entityMetaDataUpdate()
     */
    public function entityMetaDataUpdate() {
        $source = $this->entity->bundle->entity->getSource();
        $configuration = $source->getConfiguration();
        $slide_path = $this->entity->{$configuration['source_field']}->getString();

        $path_parts = explode('/', $slide_path);
        $image_name = end($path_parts); // the image name should be the last item here
        $folder = $path_parts[count($path_parts)-2] ?? '';  //get the folder too, good idea when there are images with same name
        $file = $this->writeSlideshowItemFile($slide_path, $image_name, $folder);
        if ($file) {
            $this->entity->name = $file->getFilename();
            $this->entity->field_remote_thumbnail_url = $slide_path;
            $this->entity->field_slideshow_item_image =  [ 
                'target_id' => $file->id(),
                'alt' => $file->getFilename(),
                'title' => $file->getFilename(),
            ];
            $this->entity->save();
        }

        return $this->entity;
    }

    private function writeSlideshowItemFile($filename, $image_name, $folder = '') {
        $context = stream_context_create(array(
            'http' => array('timeout' =>  10),
        ));
        $img =  $filename ? @file_get_contents($filename, false, $context) : '';  //@ used to suppress warning.  we don't want warnings!
        if($img === false) { //404 errors should be trapped like this
            return false;
        }
        $file_path = 'public://scitalk-thumbs/slides/';
        $file_path = empty($folder) ? $file_path : $file_path . $folder .'/';
        if (\Drupal::service('file_system')->prepareDirectory($file_path, FileSystemInterface::CREATE_DIRECTORY)) {
            $img_filename = $file_path  . $image_name;
            $file = \Drupal::service('file.repository')->writeData($img, $img_filename, FileExists::Replace);
            if ($file) {
                return $file;
            }
        }
        return false;
    }
}
