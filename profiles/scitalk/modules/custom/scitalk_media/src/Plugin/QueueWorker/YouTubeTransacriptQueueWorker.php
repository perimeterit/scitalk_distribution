<?php
namespace Drupal\scitalk_media\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\SuspendQueueException;
// use Drupal\Core\Queue\RequeueException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use MrMySQL\YoutubeTranscript\Exception\TooManyRequestsException;
use MrMySQL\YoutubeTranscript\Exception\YouTubeRequestFailedException;
use MrMySQL\YoutubeTranscript\Exception\TranscriptsDisabledException;
use MrMySQL\YoutubeTranscript\Exception\NoTranscriptAvailableException;
use Exception;

/**
 * @QueueWorker(
 *   id = "youtube_transcripts_queue",
 *   title = @Translation("Youtube Transcripts worker"),
 *   cron = {"time" = 60}
 * )
 */
class YouTubeTransacriptQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

   /**
   * The queue ID.
   *
   * @var string
   */
  protected $queueId;
  

  protected $queueFactory;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, EntityTypeManagerInterface $entity_type_manager, QueueFactory $queueFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queueId = $plugin_id;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queueFactory;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('scitalk_media'),
      $container->get('entity_type.manager'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    if (!empty($data)) {
      $uuid = $data ?? 0;
      $entity = $this->entityTypeManager->getStorage('node')->loadByProperties(['uuid' => $uuid]);
      if (!empty($entity)) {
        try {
          $entity = current($entity);
          $id = $entity->id();
          $this->createTalkVTT($entity);
          $entity->save();;
        }
        catch (TranscriptsDisabledException | NoTranscriptAvailableException $e) {
          //no trascript, remove from queue and continue
        }
        catch (TooManyRequestsException | YouTubeRequestFailedException | Exception $e) {
          $message = $e->getMessage();
          $this->logger->error('Pull Youtube transcript for node @nid failed: @er', ['@nid' => $id, '@er' => $message]);

          // when too many request error is triggered, need to stop processing the queue until after midnight PT
          // so create state var to notify about this
          if (strtolower($message) == 'too many requests') {
            $midnight = new \DateTime('tomorrow midnight');
            $timePlusThreeHours = $midnight->modify('+3 hours');
            $next_run = $timePlusThreeHours->format('Y-m-d H:i:s');
            $next_run = strtotime($next_run);
            $state = \Drupal::state();
            $state->set('scitalk_media.too_many_run_next', $next_run);
            $next_formatted = date("Y-m-d H:i:s", $next_run);

            $this->logger->error('Too many request made. Next run should be after @n', ['@n' => $next_formatted]);
          }
          
          // throwing an exception here will put the item back into the queue:
          throw $e;
          // throw new SuspendQueueException($e->getMessage());
        }
      }
    }
  }

  private function createTalkVTT(EntityInterface $entity) {
    $node_type = $entity->getType();
    if ($node_type == 'talk') {
      $this->getYoutubeVTTs($entity);
      $transcriptMediaService = \Drupal::service('scitalk_media.create_transcript_media');
      $transcriptMediaService->createFromVTT($entity);
    }
  }

  private function getYoutubeVTTs(EntityInterface $entity) {
    if ($entity->getType() == 'talk') {
      $target_id = $entity?->field_talk_video?->target_id ?? 0;
      $video = \Drupal::entityTypeManager()->getStorage('media')->load($target_id);
      if (!empty($video)) {
        if ($video->bundle() == 'scitalk_youtube_video') {
          $video_url = $video->field_media_scitalk_video->value ?? '';
          @preg_match_all("/^(?:https?:\/\/)?(?:(?:www\.)?youtube.com\/watch\?v=|youtu.be\/)([-\w]+)$/", $video_url, $matches);
          // include embedded utube? ex. https://www.youtube.com/embed/aqz-KE-bpKQ
          // @preg_match_all("/^(?:https?:\/\/)?(?:(?:www\.)?youtube.com\/watch\?v=|youtu.be\/|(?:www\.)?youtube.com\/embed\/)([-\w]+)$/", $video_url, $matches);
          $video_id = $matches[1] ? current($matches[1]) : '';

          // get vtts
          $utubeVTTService = \Drupal::service('scitalk_media.create_youtube_vtt');
          $utubeVTTService->create($entity, $video_id);
        }
      }
    }
  }
}