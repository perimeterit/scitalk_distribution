<?php

namespace Drupal\scitalk_media;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Queue\DelayableQueueInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;

use MrMySQL\YoutubeTranscript\Exception\TooManyRequestsException;
use MrMySQL\YoutubeTranscript\Exception\YouTubeRequestFailedException;
use MrMySQL\YoutubeTranscript\Exception\TranscriptsDisabledException;

/**
 * Runs the queue with a set time delay.
 *
 * Used to run our YouTube queue items to pull vtts, outside the cron runner.
 */
class QueueProcessor implements QueueProcessorInterface {

  /**
   * Creates a QueueProcessor object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory service.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queueWorkerManager
   *   The queue worker manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The queue_processor_example logging service.
   */
  public function __construct(
    protected QueueFactory $queueFactory,
    protected QueueWorkerManagerInterface $queueWorkerManager,
    protected TimeInterface $time,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritDoc}
   */
  public function queueHasItems(string $type):bool {
    $queue = $this->queueFactory->get($type);

    if ($queue->numberOfItems() === 0) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function run(string $type, int $time = 60) {
    $queue = $this->queueFactory->get($type);
    $worker = $this->queueWorkerManager->createInstance($type);

    if ($queue->numberOfItems() === 0) {
      // Queue is empty, so return here.
      return;
    }

    $pluginDefinition = $worker->getPluginDefinition();
    $leaseTime = $time ?? $pluginDefinition['cron']['time'];
    // $leaseTime = $pluginDefinition['cron']['time'] ?? $time;
    $end = $this->time->getCurrentTime() + $leaseTime;
   
    // Libraries like youtube-transcript-api (Python) or similar tools often scrape the YouTube website directly, rather than using the official API. 
    // These methods are subject to YouTube's direct rate limits and anti-automation measures, which are stricter: 
    // You are typically limited to about 5 requests per 10 seconds.
    // Exceeding this may result in a 429 Too Many Requests error or a temporary IP block.
    // Implementing delays (throttling) in your code is essential to avoid being blocked. 
    // 
    // so we are processing 4 items every x random seconds for the duration of the cron lease time (eg. 60s):

    $totalProccessed = 0;
    $pauseFor = 15; //pause for 15 seconds before continuing
    $maxItemsToProcess = 4;
    $itemsProcessed = 0;
    while ($this->time->getCurrentTime() < $end && ($item = $queue->claimItem($leaseTime))) {
      try {
        if ($itemsProcessed < $maxItemsToProcess) {
          // add some delays between request to avoid triggering anti-bot systems.
          $wait = rand(10, 25);
          sleep($wait);
          
          $worker->processItem($item->data);
          $queue->deleteItem($item);
          $itemsProcessed++;
          $totalProccessed++;
        }
        else {
          // after $maxItemsProcessed, pause for $pauseFor seconds, then continue as long as within the $leaseTime
          $itemsProcessed = 0;
          $queue->releaseItem($item);

          $pauseFor = rand(25, 40);
          sleep($pauseFor);
          // throw new DelayedRequeueException(15, 'delaying for 15 secs');          
        }
      }
      catch (DelayedRequeueException $e) {
        // The worker requested the task not be immediately re-queued.
        // - If the queue doesn't support ::delayItem(), we should leave the
        // item's current expiry time alone.
        // - If the queue does support ::delayItem(), we should allow the
        // queue to update the item's expiry using the requested delay.
        if ($queue instanceof DelayableQueueInterface) {
          // This queue can handle a custom delay; use the duration provided
          // by the exception.
          $queue->delayItem($item, $e->getDelay());
        }
      }
      catch (RequeueException) {
        // The worker requested the task be immediately requeued.
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException | TooManyRequestsException | YouTubeRequestFailedException $e) {
        // If the worker indicates the whole queue should be skipped, release
        // the item and go to the next queue.
        $queue->releaseItem($item);

        $this->logger->debug('A worker for @queue queue suspended further processing of the queue: @e.', [
          '@queue' => $worker->getPluginId(), '@e' => $e->getMessage()
        ]);

        // Skip to the next queue.
        throw $e;
      }
      catch (TranscriptsDisabledException $e) {
        //no transcript for this video, skip it and remove from q
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        Error::logException($this->logger, $e);
      }
    }

    $this->logger->debug('Youtube Queue worker finished processing @er.', ['@er' => $totalProccessed]);
    return $totalProccessed;
  }

}