<?php

namespace Drupal\scitalk_base\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Exception\SkipItemException;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\media\Entity\Media;

/**
 * Reacts on talks being processed.
 */
class feedAlterTalks extends AfterParseBase {

  /**
   * {@inheritdoc}
   */
  public function applies(ParseEvent $event) {
    $feed_id = $event->getFeed()->getType()->id();
    $talk_feeds = [
      'talk_importer_no_dependencies',
      'talk_importer',
      'talk_importer_inclusive_csv',
      'talk_importer_inclusive_json'
    ];

    return in_array($feed_id, $talk_feeds);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {

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
      $item->set('_video_url','');

    }
  }
}
