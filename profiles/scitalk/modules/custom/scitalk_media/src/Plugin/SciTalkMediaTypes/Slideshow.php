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
 *   id = "SciTalkSlideshow",
 *   description = @Translation("SlideShow item plugin."),
 *   media_type = "scitalk_slideshow",
 *   media_source = "",
 * )
 */
class Slideshow extends SciTalkMediaPluginBase {
   
  
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'SciTalkSlideshow';
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
    // don't add items if the slideshow already has items
    $slideshow_empty = $this->entity->get('field_slideshow_items')->isEmpty();
    if (!$slideshow_empty) {
        return;
    }

    // THIS NEEDS TO GO INTO PIRSA, NOT SURE HOW TO HANDLE THIS IN THE DISTRO (PULL IMAGES FROM SOME REMOTE FOLDER
    // FOR WHICH I DON'T KNOW THE FILE STRUCTURE)
    $source = $this->entity->bundle->entity->getSource();
    $configuration = $source->getConfiguration();
    $remote_images_folder = $this->entity->{$configuration['source_field']}->getString();

    $manager = \Drupal::service('plugin.manager.scitalk_media_types');
    $slideshow_item_plugin_id = 'SciTalkSlideshowItem';
    
    $page = new \DOMDocument();
    $page->loadHTMLFile(filename: $remote_images_folder);
    $xpath = new \DOMXPath($page);
    //the images are the 2d td within each tr:
    $images = @$xpath->query('//td[position() = 2]');
    if (!empty($images)) {
        foreach ($images as $idx => $image) {
            $image_name = $image->nodeValue;
            $arr = explode('.', $image->nodeValue);
            $arr_len = count($arr);
            if ($arr_len > 1 && in_array( $arr[$arr_len -1], ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                if (substr($remote_images_folder, -1) != '/') {
                    $remote_images_folder .= '/';
                }
                $slide_path = $remote_images_folder . $image_name;

                $media = ['bundle' => 'scitalk_slideshow_item', 'field_remote_thumbnail_url'=> $slide_path];
                $new_media = \Drupal::entityTypeManager()->getStorage('media')->create($media);
                $slideshow_item_plugin = $manager->createInstance($slideshow_item_plugin_id, [$new_media]);
                $media_item = $slideshow_item_plugin->entityInsert();
                $this->entity->field_slideshow_items[] = ['target_id' => $media_item->id()];
            }
        }
        $this->entity->save();
    }
  }
}
