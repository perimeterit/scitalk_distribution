<?php
namespace Drupal\scitalk_media\Commands;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drush\Commands\DrushCommands;

/**
 * Drush commands that add items to a queue.
 */
class YouTubeQueueCommands extends DrushCommands {
    /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

//    /**
//    * Logging channel.
//    *
//    * @var \Drupal\Core\Logger\LoggerChannelInterface
//    */
//   protected $logger;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  protected $queueProcessor;

  /**
   * Constructs a new QueueCommands object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory service.
   */
  public function __construct(ContainerInterface $container, LoggerChannelFactoryInterface $loggerChannelFactory, QueueFactory $queueFactory) {
    $this->queueProcessor = $container->get('scitalk_media.youtube_queue_runner');
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->queueFactory = $queueFactory;
  }

  /**
   * Command to pull vtt's from YouTube for exisiting entities in the queue
   * 
   * @command scitalk_media:pull-youtube-vtts
   * @aliases scitalk_media:utube-vtts
   * @usage scitalk_media:pull-youtube-vtts
   */
  public function pullVTTs() {
    $queueProcessor = $this->queueProcessor;
    if (!$queueProcessor->queueHasItems('youtube_transcripts_queue') ) {
        $this->logger()->notice("Queue 'youtube_transcripts_queue' is empty.");
        return;
    }

    $proccessed = $queueProcessor->run('youtube_transcripts_queue');
    $this->logger()->notice("Pulled vtts from YouTube: " . $proccessed);
  }

  /**
   * Populate a queue for testing.
   *
   * @command scitalk_media:populate
   * @param array $options
   * @usage scitalk_media:populate
   */
  public function populateQueue($options = ['number' => 10]) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get('youtube_transcripts_queue');
    for ($i = 0; $i < $options['number']; $i++) {
      $queue->createItem($i);
    }

    $this->logger()->notice("Queue populated with $i items.");
  }
}