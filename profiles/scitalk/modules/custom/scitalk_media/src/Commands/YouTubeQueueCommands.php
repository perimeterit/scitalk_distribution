<?php
namespace Drupal\scitalk_media\Commands;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
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
  protected $time;

  /**
   * Constructs a new QueueCommands object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory service.
   */
  public function __construct(ContainerInterface $container, LoggerChannelFactoryInterface $loggerChannelFactory, QueueFactory $queueFactory, TimeInterface $time) {
    $this->queueProcessor = $container->get('scitalk_media.youtube_queue_runner');
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->queueFactory = $queueFactory;
    $this->time = $time;
  }

  /**
   * Command to pull vtt's from YouTube for exisiting entities in the queue
   * 
   * @command scitalk_media:pull-youtube-vtts
   * * @param array $options
   * @aliases scitalk_media:utube-vtts
   * @usage scitalk_media:pull-youtube-vtts
   */
  public function pullVTTs($options = ['time' => 60]) {
    $queueProcessor = $this->queueProcessor;
    if (!$queueProcessor->queueHasItems('youtube_transcripts_queue') ) {
        $this->logger()->notice("Queue 'youtube_transcripts_queue' is empty.");
        return;
    }

    $state = \Drupal::state();
    $next_run = $state->get('scitalk_media.too_many_run_next') ?? false;
    if ($next_run) {
        if ($next_run > $this->time->getCurrentTime()) {
            $next_formatted = date("Y-m-d H:i:s", $next_run);
            $this->logger()->notice("Too many requests were made to Youtube, next run will be after: ". $next_formatted );
            return;
        }
        else {
            $state->delete('scitalk_media.too_many_run_next');
        }
    }

    $proccessed = $queueProcessor->run('youtube_transcripts_queue', $options['time']);
    $this->logger()->notice("Pulled vtts from YouTube: " . $proccessed);
  }

  /**
   * Command to return the number of items in the queue
   *
   * @command scitalk_media:items
   * @usage scitalk_media:items
   */
  public function itemsInQueue() {
    $queueProcessor = $this->queueProcessor;
    $numItems = $queueProcessor->queueItems('youtube_transcripts_queue');
     $this->logger()->notice("Number of items in the queue: " . $numItems);
  }

//   /**
//    * Populate a queue for testing purposes.
//    *
//    * @command scitalk_media:populate
//    * @param array $options
//    * @usage scitalk_media:populate
//    */
//   public function populateQueue($options = ['number' => 10]) {
//     /** @var \Drupal\Core\Queue\QueueInterface $queue */
//     $queue = $this->queueFactory->get('youtube_transcripts_queue');
//     for ($i = 0; $i < $options['number']; $i++) {
//       $queue->createItem($i);
//     }

//     $this->logger()->notice("Queue populated with $i items.");
//   }
}