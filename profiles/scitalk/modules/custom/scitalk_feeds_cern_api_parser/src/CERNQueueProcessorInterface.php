<?php

namespace Drupal\scitalk_feeds_cern_api_parser;

/**
 * Interface for the QueueProcessor service.
 */
interface CERNQueueProcessorInterface {

  /**
   * Run the queue with a give time limit.
   *
   * The time limit is defined in the queue worker plugin, but can optionally
   * be passed to this plugin to change the execution time. If the time is
   * not defined at all then it will default to 10 seconds.
   *
   * Much of this code is taken from the core Cron implementation of timed
   * queue workers.
   *
   * @param string $type
   *   The type of queue to run.
   * @param int $time
   *   The amount of time to default the processor to. This is used if there
   *   is no value set for the time to run of the cron plugin.
   *
   * @see \Drupal\Core\Cron::processQueue()
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function run(string $type, int $time = 10);

  /**
   * Find out if the queue has and items in it.
   *
   * @param string $type
   *   The queue type.
   *
   * @return bool
   *   True if the queue has items to process.
   */
  public function queueHasItems(string $type):bool;

  /**
   * Find out how many items in it.
   *
   * @param string $type
   *   The queue type.
   *
   * @return bool
   *   True if the queue has items to process.
   */
  public function queueItems(string $type):int;

}