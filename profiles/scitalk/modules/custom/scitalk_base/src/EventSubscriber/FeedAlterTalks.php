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
      'talk_importer_inclusive_csv'
    ];

    return in_array($feed_id, $talk_feeds);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {

    // Set the talk source repository field to the group
    // Make feed_item unique id use the Group's Talk prefix + the Talk number
    $group_field = $event->getFeed()->get('field_feeds_group')->getValue();

    if (isset($group_field[0])) {
      $group_id = $group_field[0]['target_id'];
      $group =  \Drupal::entityTypeManager()->getStorage('group')->load($group_id);
      $prefix_field = $group->get('field_repo_talks_prefix')->getValue();

      // Set the source group value to the group id.
      $item->set('source_group', $group_id);

      // Find the prefix for this group
      if (isset($prefix_field[0])) {
        $prefix = $prefix_field[0]['value'];
      } else {
        // If not use the group id
        $prefix = $group_id . '-';
      }
    } else {
      // If no group is set, use the prefix from scitalk base settings
      $scitalk_base_config = \Drupal::config('scitalk_base.settings');
      $prefix = $scitalk_base_config->get('datacite_talk_prefix') ?? '';
    }

    // Assign the prefix + talk number to the prefix talk number custom source
    // This value is used for the Feeds Item unique id
    $prefixed_talk_number = $prefix . $item->get('_talk_number');
    $item->set('prefixed_talk_number', $prefixed_talk_number);

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
