<?php

namespace Drupal\scitalk_media\Plugin\SciTalkMediaTypes;

use Drupal\scitalk_media\SciTalkMediaPluginBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileExists;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;


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
        $id = $this->entity->id();
        if (!empty($id)) {
            return; // if the entity already has an id, it means it's already been inserted, so we don't want to do anything here
        }
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

        // fetch the image asynchronously to avoid timeouts, since some slideshows have a lot of images and it can take a while to fetch them all
        $future =  \Amp\async(fn() => $this->asyncFetchSlideshowItemFile($slide_path));
        $img = \Amp\Future\await([$future]);
        if (!$img) {
            return;
        }
        $file = $this->writeFile($img[0], $image_name, $folder);
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

    /**
     * Fetch the remote slides asynchronously to avoid timeouts, since some slideshows have a lot of images and it can take a while to fetch them all.
     * @param string $filename The URL of the image to fetch
     * @return string|bool The image data as a string if the image was successfully fetched, or false if there was an error fetching the image
     */
    private function asyncFetchSlideshowItemFile(string $filename): string|bool {
        $img = '';
        if ($filename) {
            $client = HttpClientBuilder::buildDefault();

            try {
                $future = \Amp\async(function() use ($client, $filename) {
                    $request = new Request($filename, 'GET');
                    $request->setTransferTimeout(30000); // set timeout to 30 seconds
                    $request->setInactivityTimeout(30000); // set inactivity timeout to 30 seconds
                    return $client->request($request);
                });
                
                $img = \Amp\Future\await([$future]);
                $img = (string) $img[0]->getBody();
                return $img;
            }
            catch (\Exception $e) {
                \Drupal::logger('scitalk_media')->error('Error fetching image @filename: @message', ['@filename' => $filename, '@message' => $e->getMessage()]);
                return false;
            }
        }
        return $img;
    }

    /**
     * Write the fetched image data to a file in the local file system and return the file entity.  
     * This can't be done asynchronously because Drupal's file system functions are not designed to work with async code, but the image fetching can be done asynchronously to improve performance.
     * @param string $img
     * @param string $image_name
     * @param string $folder
     * @return \Drupal\file\FileInterface|bool
     */
    private function writeFile(string $img,  string $image_name, string $folder = ''): \Drupal\file\FileInterface|bool {
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

    // fetch blocking code for reference, old code should be removed once we're sure the async version is working well:
    // /**
    //  * fetch remote slides and create media entities for each slide, then create slideshow item plugins for each media entity and add them to the slideshow
    //  * @param string $filename
    //  * @param string $image_name
    //  * @param string $folder
    //  * @return \Drupal\file\Entity\File|bool
    //  */
    // private function writeSlideshowItemFile(string $filename, string $image_name, string $folder = ''): \Drupal\file\Entity\File|bool {
    //     $context = stream_context_create(array(
    //         'http' => array('timeout' =>  10),
    //     ));
    //     $img =  $filename ? @file_get_contents($filename, false, $context) : '';  //@ used to suppress warning.  we don't want warnings!
    //     if($img === false) { //404 errors should be trapped like this
    //         return false;
    //     }
    //     $file_path = 'public://scitalk-thumbs/slides/';
    //     $file_path = empty($folder) ? $file_path : $file_path . $folder .'/';
    //     if (\Drupal::service('file_system')->prepareDirectory($file_path, FileSystemInterface::CREATE_DIRECTORY)) {
    //         $img_filename = $file_path  . $image_name;
    //         $file = \Drupal::service('file.repository')->writeData($img, $img_filename, FileExists::Replace);
    //         if ($file) {
    //             return $file;
    //         }
    //     }
    //     return false;
    // }
}
