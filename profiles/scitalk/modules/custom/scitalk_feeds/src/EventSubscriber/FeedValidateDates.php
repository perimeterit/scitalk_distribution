<?php

namespace Drupal\scitalk_feeds\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Exception\SkipItemException;

/**
 * Validate dates for talks and collections
 * Reacts on items being processed.
 */

class feedValidateDates extends AfterParseBase {

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

      $feed_id = $event->getFeed()->getType()->id();
      $item = $result->offsetGet($i);

      // For talk feeds
      if ($feed_id != 'collection_import') {
        $date_value = $item->get('_date');

        $validate_talk_date = _validateDate($date_value);
        if ($validate_talk_date == FALSE) {
          $item_number = $item->get('_talk_number');
          \Drupal::messenger()->addWarning(t('The talk date ' . $date_value . ' is not valid for item number ' . $item_number));
          $item->set('_date', '');
        }
      }

      // Collection feed. Validates start and end dates.
      else {
        $start_date_value = $item->get('_start_date');
        $end_date_value = $item->get('_end_date');
        $validate_start_date = _validateDate($start_date_value);
        if ($validate_start_date == FALSE) {
          $item_number = $item->get('_collection_number');
          \Drupal::messenger()->addWarning(t('The start date ' . $start_date_value . ' is not valid for item number ' . $item_number));
          $item->set('_start_date', '');
        }

        $validate_end_date = _validateDate($end_date_value);
        if ($validate_end_date == FALSE) {
          $item_number = $item->get('_collection_number');
          \Drupal::messenger()->addWarning(t('The end date ' . $end_date_value . ' is not valid for item number ' . $item_number));
          $item->set('_end_date', '');
        }
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

/**
 ** Common function to check dates
 */
function _validateDate($date) {

  // Test for numbers that aren't a valid date (e.g. a 5 digit number)
  // These get converted to Unix timestamps on import, so we want to
  // Make sure they are actually valid dates and not another number
  if (($date == FALSE) || (empty($date)) || ($date == '')) {
    // This is empty
    return TRUE;
  }
  else if (strtotime($date) &&
    (is_numeric($date) && (strlen($date) != 10))) {
    // A number that isn't a valid date
    return FALSE;
  }
  else if (strtotime($date)) {
    // This is valid date string
    return TRUE;
  }
  else {
    // Another case, not a valid date string.
    return FALSE;
  }
}
