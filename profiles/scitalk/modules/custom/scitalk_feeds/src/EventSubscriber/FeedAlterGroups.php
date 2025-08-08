<?php

namespace Drupal\scitalk_feeds\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * Alter items on feed import for Groups
 *  - assign to the right group on import
 *  - prefix the talk and collection numbers with the Group's talk prefix
 * Reacts on talks being processed.
 */

class feedAlterGroups extends AfterParseBase {

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
      'collection_import',
      'youtube_talk_importer',
    ];

    return in_array($feed_id, $feeds);
  }

  /**
   * Alter the feeds item on import.
   * This will assign the node to the right group and create a unique feed ID
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {
    // Get the group field value from this feed
    $group_field = $event->getFeed()->get('field_feeds_group')->getValue();
    if (isset($group_field[0])) {
      $group_id = $group_field[0]['target_id'];
      $group = \Drupal::entityTypeManager()->getStorage('group')->load($group_id);
      $prefix_field = $group->get('field_repo_talks_prefix')->getValue();

      // Source group is a custom field that maps to the source reference field
      $item->set('source_group', $group_id);

      /**
       * Set a unique feeds ID number using the talk prefix and talk number
       * If the group has a talk prefix, use that plus the Talk number
       * If that is empty, and there is a group, use the Group ID plus the Talk number
       * If there is no group assigned, use the Scitalk base module talk prefix
       */
      // Find the prefix for this group
      if (isset($prefix_field[0])) {
        $prefix = $prefix_field[0]['value'];
      }
      else {
        // If it's empty use the group id
        $prefix = $group_id . '-';
      }
    }
    else {
      // If no group is set, use the prefix from scitalk base settings
      $scitalk_base_config = \Drupal::config('scitalk_base.settings');
      $prefix = $scitalk_base_config->get('datacite_talk_prefix') ?? '';
    }


    $feed_id = $event->getFeed()->getType()->id();

    // Assign the prefix + talk number to the prefix talk number custom source
    // This value is used for the Feeds Item unique id
    if ($feed_id == 'collection_import') {
      $prefixed_collection_number = $prefix . $item->get('_collection_number');
      $item->set('prefixed_collection_number', $prefixed_collection_number);
    }
    else {
      if (!empty($item->get('_talk_number'))) {
        $prefixed_talk_number = $prefix . $item->get('_talk_number');
        $item->set('prefixed_talk_number', $prefixed_talk_number);
      }
    }
  }
}
