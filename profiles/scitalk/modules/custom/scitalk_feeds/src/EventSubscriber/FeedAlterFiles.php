<?php

namespace Drupal\scitalk_feeds\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Fixes for importing file attachments
 *   - Copy file attachments to the file system and create a file entity
 *   - For YouTube videos, make a Scitalk YouTube media entity
 * Reacts on talks being processed.
 */
class feedAlterFiles extends AfterParseBase {

  /**
   * {@inheritdoc}
   */
  public function applies(ParseEvent $event) {
    $feed_id = $event->getFeed()->getType()->id();
    $talk_feeds = [
      'talk_importer_no_dependencies',
      'talk_importer',
      'talk_importer_inclusive_csv',
      'talk_importer_inclusive_json',
      'collection_import',
    ];

    return in_array($feed_id, $talk_feeds);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {

    // Fix file imports.
    // There is an issue for fixing this in Feeds module, but it currently
    // only works for standard media entity
    $attachment_url = $item->get('_attachments');

    if (!empty($attachment_url)) {
      // Set the storage directory and destination for this file
      $directory = 'public://attachments/';
      $destination = $directory . basename($attachment_url);

      // Retrieve the file
      try {
        $data = (string) \Drupal::httpClient()->get($attachment_url)->getBody();
        $file = \Drupal::service('file_system')->saveData($data, $destination, FileSystemInterface::EXISTS_REPLACE);
      }
      catch (TransferException $exception) {
        \Drupal::messenger()->addError(t('Failed to fetch file due to error "%error"', ['%error' => $exception->getMessage()]));
      }
      catch (FileException | InvalidStreamWrapperException $e) {
        \Drupal::messenger()->addError(t('Failed to save file due to error "%error"', ['%error' => $e->getMessage()]));
      }

      // Create and save a new file entity.
      $file = File::create([
        'filename' => basename($attachment_url),
        'uri' => 'public://attachments/' . basename($attachment_url),
        'status' => 1,
        'uid' => 1,
      ]);
      $file->save();


      // Now create and save a new media entity
      $attachment_media = Media::create([
        'bundle' => 'file',
        'uid' => 1,
        'name' => $item->get('_title'),
        'field_media_file' => [
          'target_id' => $file->id(),
        ],
      ]);

      // Save the media entity
      $attachment_media->save();

      // Set the value of the attachments field to this media id
      $item->set('_attachments', $attachment_media->id());
    }

    // If video URL is from youtube, make it create a Scitalk YouTube entity
    $video_url = $item->get('_video_url');
    if ((!empty($video_url)) && (!empty(str_contains($video_url, 'youtube')))) {
      // Create the media entity
      $video_media = Media::create([
        'bundle' => 'scitalk_youtube_video',
        'uid' => 1,
        'name' => $item->get('_title'),
        'field_media_scitalk_video' => [
          'value' => $video_url,
        ],
      ]);
      $video_media->save();

      // Then set the value of the reference for the video field
      $item->set('_video', $video_media->getName());

      // Then unset the video link value
      $item->set('_video_url', '');
    }
  }
}
