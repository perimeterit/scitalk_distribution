<?php
namespace Drupal\scitalk_feeds_cern_api_parser\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Exception;

/**
 * @QueueWorker(
 *   id = "scitalk_feeds_cern_subtitle_downloader_queue",
 *   title = @Translation("CERN API Parser worker"),
 *   cron = {"time" = 60}
 * )
 */
class CERNQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
    

    /**
     * {@inheritDoc}
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->queueId = $plugin_id;
        $this->logger = $logger;
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * {@inheritDoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('logger.factory')->get('scitalk_feeds_cern_api_parser'),
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function processItem($data) {
        if (!empty($data)) {
            $talk_number = $data['talk_number'] ?? 0;
            $source_repo_id = $data['source_repo_id'] ?? 0;
            $subtitles = $data['subtitles'] ?? '';

            $properties = [
                "field_talk_number" => $talk_number,
                "type" => "talk",
                "field_talk_source_repository" => $source_repo_id,
            ];

            $entity = $this->entityTypeManager->getStorage('node')->loadByProperties($properties);
            if (!empty($entity)) {
                try {                    
                    $entity = current($entity);
                    $has_subtitles = $entity->hasField('field_subtitle_url') && !$entity->get('field_subtitle_url')->isEmpty();
                    if ($has_subtitles) {
                        // $this->logger->notice('Entity with talk number @talk_number already has subtitles, skipping. Entity ID: @nid', ['@talk_number' => $talk_number, '@nid' => $entity->id()]);
                        return;
                    }

                    $subtitles_media = $this->createSubtitleMedia($entity, $subtitles);
                    if ($subtitles_media) {
                        $entity->set('field_subtitle_url', $subtitles_media->id());
                        $entity->save();
                    }
                }
                catch (Exception $e) {
                    $message = $e->getMessage();
                    $this->logger->error('Pull CERN transcript for node @nid failed: @er', ['@nid' => $entity->id(), '@er' => $message]);
                }
            }
        }
    }
    private function createSubtitleMedia(EntityInterface $entity, $subtitles_url) {
        // if there is a subtitle url, create a media entity for it
        if (!empty($subtitles_url)) {
            $title = $entity->title->value ?? 'subtitles';
            $subtitle_media = [
                'bundle' => 'subtitles_url',
                'uid' => 1,
                'name' => $title,
                'field_media_scitalk_remote_file' => $subtitles_url,
                'field_subtitles_language' => 'en' // always use english
            ];

            $subtitle_media_entity = \Drupal::entityTypeManager()->getStorage('media')->create($subtitle_media);
            $subtitle_media_entity->save();
            return $subtitle_media_entity;
        }
        return null;
    }
}