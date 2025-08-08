<?php

namespace Drupal\scitalk_feeds\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Feeds\Item\ItemInterface;
use \Drupal\feeds\FeedInterface;
use Drupal\feeds\Exception\SkipItemException;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\Exception\FileException;
use GuzzleHttp\Exception\TransferException;
// use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityInterface;

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
      'youtube_talk_importer',
    ];

    return in_array($feed_id, $talk_feeds);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {

    // get the entity for this feed item:
    $entity = $this->existingEntity($event->getFeed(), $item);
    
    // if the the skip_feeds_update flag has been checked for the drupal entity, then don't update:
    if (!empty($entity)) {
      $skip_updates = $entity->get('field_skip_feeds_updates')->value ?? false;
      if ($skip_updates) {
        $talk_number = $item->get('prefixed_talk_number') ?? $entity->field_talk_number?->value ?? $item->get('prefixed_collection_number') ?? $item->get('_collection_number');
        $msg = "Skipping feed import for <b>'{$entity->title->value}'</b> ({$talk_number})";
        \Drupal::messenger()->addMessage(t($msg));
        throw new SkipItemException($msg);
      }
    }

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
        $file = \Drupal::service('file_system')->saveData($data, $destination, FileExists::Replace);
      }
      catch (TransferException $exception) {
        \Drupal::messenger()->addError(t('Failed to fetch file due to error "%error"', ['%error' => $exception->getMessage()]));
      }
      catch (FileException | InvalidStreamWrapperException $e) {
        \Drupal::messenger()->addError(t('Failed to save file due to error "%error"', ['%error' => $e->getMessage()]));
      }

      // check if the attached file in the entity is the same as the one coming from the feed
      // if it's the same then re-use it, don't recreate, else create the file
      $entity_attached = $this->getEntityAttachment($entity);
      $entity_attached_file = $this->getEntityAttachmentFile($entity_attached);
      $entity_attached_filename = $entity_attached_file?->get('uri')->value ?? '';
      if (basename($attachment_url) == basename($entity_attached_filename)) {
        $attachment_media_id = $entity_attached->id();
        $item->set('_attachments', $attachment_media_id);
      }
      else {
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
            'description' => $item->get('_title'),
          ],
        ]);
  
        // Save the media entity
        $attachment_media->save();
  
        // Set the value of the attachments field to this media id
        $item->set('_attachments', $attachment_media->id());
      }
    }

    // should a YouTube video be embeded?
    $create_youtube = $event->getFeed()?->field_embed_youtube?->value ?? false;
    if (!$create_youtube) {
      return;
    }

    $feed_id = $event->getFeed()->getType()->id();
    $video_url_field = '_video_url';
    $video_title_field = '_title';
    $video_field = '_video';

    // these are the field names for the youtube importer:
    if ($feed_id == 'youtube_talk_importer') {
      $video_url_field = 'video_url';
      $video_title_field = 'title';
      $video_field = 'video';
    }

    // If video URL is from youtube, make it create a Scitalk YouTube entity
    $video_url = $item->get($video_url_field);
    @preg_match_all("/^(?:https?:\/\/)?(?:(?:www\.)?youtube.com\/watch\?v=|youtu.be\/)([-\w]+)$/", $video_url, $matches);
    if ((!empty($video_url)) && (!empty($matches[0]))) {
      // check if this video media already exists,
      // if it does and it's for the same feed item then simply update it
      // if it does and it's not linked to the same feed item, or is not existing then create a new media and attach it to the feed item

      $entity_video = $this->getEntityVideo($entity);
      $entity_video_url = $entity_video?->get('field_media_scitalk_video')->value ?? '';

      // feed video is the same as the existing one, so reuse it:
      if ($entity_video_url == $video_url) {
        $video_media = $entity_video->getName();
        $item->set($video_field, $video_media);
        $item->set($video_url_field, '');
      }
      else {
        // Create the media entity
        $video_media = Media::create([
          'bundle' => 'scitalk_youtube_video',
          'uid' => 1,
          'name' => $item->get($video_title_field),
          'field_media_scitalk_video' => [
            'value' => $video_url,
          ],
        ]);
        $video_media->save();

        // Then set the value of the reference for the video field
        $item->set($video_field, $video_media->getName());
  
        // Then unset the video link value
        $item->set($video_url_field, '');
      }
    }
  }


  /**
   * Returns an existing entity attached to the feed item.
   * Copied and modified from:
   *     web/modules/contrib/feeds/src/Feeds/Processor/EntityProcessorBase.php
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being processed.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to find existing ids for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity, or null if not found.
   */
  private function existingEntity(FeedInterface $feed, ItemInterface $item) {
    $feedType = $feed->getType();
    foreach ($feedType->getMappings() as $delta => $mapping) {
      if (empty($mapping['unique'])) {
        continue;
      }

      foreach ($mapping['unique'] as $key => $true) {
        $plugin = $feedType->getTargetPlugin($delta);
        $entity_id = $plugin->getUniqueValue($feed, $mapping['target'], $key, $item->get($mapping['map'][$key]));
        if ($entity_id) {
          return \Drupal::entityTypeManager()->getStorage('node')->load($entity_id);
        }
      }
    }
    return null;
  }

   /**
   * Returns the Video media entity linked to a feed item.
   *
   * @param  \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity to find media for.
   *
   * @return \Drupal\node\Entity||null
   *   The media entity, or null if not found.
   */
  private function getEntityVideo(EntityInterface|null $entity) {
    if ($entity instanceof EntityInterface) {
      $video_target_id = $entity->get('field_talk_video')->target_id;
      $media_entity = \Drupal::entityTypeManager()->getStorage('media')->load($video_target_id);
      return $media_entity;
    }

    return null;
  }

   /**
   * Returns the attachment media entity linked to a feed item.
   *
   * @param  \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity to find media for.
   *
   * @return \Drupal\node\Entity||null
   *   The attachment entity, or null if not found.
   */
  private function getEntityAttachment(EntityInterface|null $entity) {
    if ($entity instanceof EntityInterface) {
      $attachment_target_id = $entity->get('field_talk_attachments')?->target_id ?? -1;
      $media_entity = \Drupal::entityTypeManager()->getStorage('media')->load($attachment_target_id);
      return $media_entity;
    }

    return null;
  }

  /**
   * Returns the File media in the attachment media entity linked to a feed item.
   *
   * @param  \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity to find media for.
   *
   * @return \Drupal\node\Entity||null
   *   The attachment entity, or null if not found.
   */
  private function getEntityAttachmentFile(EntityInterface|null $media_entity) {
    $mid = $media_entity?->id() ?? -1;
    $media_file_entity = \Drupal::entityTypeManager()->getStorage('media')->load($mid);
    $file_id = $media_file_entity?->get('field_media_file')?->target_id ?? 0;
    if ($file_id) {
      return \Drupal::entityTypeManager()->getStorage('file')->load($file_id);
    }

    return null;
  }
}
