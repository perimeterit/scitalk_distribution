<?php

namespace Drupal\scitalk_feeds_cern_api_parser;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Queue\DelayableQueueInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;


/**
 * Runs the queue with a set time delay.
 *
 * Used to run our YouTube queue items to pull vtts, outside the cron runner.
 */
class CERNQueueProcessor implements CERNQueueProcessorInterface {

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
  public function queueItems(string $type):int {
    $queue = $this->queueFactory->get($type);
    return $queue->numberOfItems() ?? 0;
  }
  
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
 
    $totalProccessed = 0;
    while ($this->time->getCurrentTime() < $end && ($item = $queue->claimItem($leaseTime))) {
      try {
        $worker->processItem($item->data);
        $queue->deleteItem($item);
        $totalProccessed++;
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
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        Error::logException($this->logger, $e);
      }
    }

    $this->logger->debug('CERN Queue worker finished processing @er.', ['@er' => $totalProccessed]);
    return $totalProccessed;
  }

}