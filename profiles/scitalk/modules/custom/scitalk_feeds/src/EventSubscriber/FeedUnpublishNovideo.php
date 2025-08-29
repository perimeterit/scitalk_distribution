<?php

namespace Drupal\scitalk_feeds\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Exception\SkipItemException;

/**
 * Unpublish talks if there is no video file or link
 * Reacts on items being processed.
 */

class FeedUnpublishNovideo extends AfterParseBase {

  /**
   * {@inheritdoc}
   */
  public function applies(ParseEvent $event) {
    $feed_id = $event->getFeed()->getType()->id();
    $feeds = [
      'talk_importer_no_dependencies',
      'talk_importer',
      'talk_importer_inclusive_csv',
      'talk_importer_inclusive_json',
      'youtube_talk_importer',
    ];

    return in_array($feed_id, $feeds);
  }

  /**
   * Acts on parser result.
   * Check to seee if the dates are valid and throws an exception
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParse(ParseEvent $event) {
    if (!$this->applies($event)) {
      return;
    }

    /** @var \Drupal\feeds\Result\ParserResultInterface $result */
    $result = $event->getParserResult();

    for ($i = 0; $i < $result->count(); $i++) {
      if (!$result->offsetExists($i)) {
        break;
      }

      /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
      $item = $result->offsetGet($i);

      // Get video and video URL fields
      $video_field = $item->get('_video');
      $video_url_field = $item->get('_video_url');

      // If they are both empty, set the item to unpublished
      if (empty($video_field) && empty($video_url_field)) {
        $item->set('status', 0);
      }

      try {
        $this->alterItem($item, $event);
      }
      catch (SkipItemException $e) {
        $result->offsetUnset($i);
        $i--;
      }
    }
  }
}
