<?php

namespace Drupal\scitalk_unused_media\Controller;

use Drupal\Core\Batch\BatchBuilder;
// use Drupal\Core\Controller\ControllerBase;
// use Drupal\Core\Entity\EntityTypeManagerInterface;
// use Drupal\Core\Logger\LoggerChannelFactoryInterface;
// use Drupal\Core\Messenger\MessengerInterface;
// use Drupal\Core\State\StateInterface;
// use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\unused_media_cleaner\Controller\MediaCleanerController;

/**
 * Controller for cleaning up unused media files.
 *
 * Provides functionality to delete media files that are not currently in use.
 */
class SciTalkMediaCleanerController extends MediaCleanerController {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Deletes unused media files based on the generated report.
   *
   * Reads the report data from Drupal state, identifies unused media IDs,
   * and initiates a batch process to delete them.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the media report page after attempting to delete unused
   *   media files.
   */
  public function deleteUnused() {
    // Retrieve report data from state.
    $report_data = $this->state->get('unused_media_cleaner.report_data', []);

    if (empty($report_data)) {
      $this->messenger->addError($this->t('No report data found. Please generate a report first.'));
      return new RedirectResponse('/admin/config/media/report');
    }

    $unused_media = [];
    foreach ($report_data as $row) {
      if (isset($row['used_on']) && $row['used_on'] === 'Not used') {
        $unused_media[] = $row['id'];
      }
    }

    if (empty($unused_media)) {
      $this->messenger->addWarning($this->t('No unused media items were found to delete.'));
      return new RedirectResponse('/admin/config/media/report');
    }

    // Create batch.
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Deleting unused media'))
      ->setInitMessage($this->t('Starting deletion process...'))
      ->setProgressMessage($this->t('Processed @current out of @total.'))
      ->setErrorMessage($this->t('An error occurred during deletion.'));

    // Split media IDs into smaller chunks for better performance.
    $chunks = array_chunk($unused_media, 25);
    foreach ($chunks as $chunk) {
      $batch_builder->addOperation([static::class, 'processMediaDeletion'], [$chunk]);
    }

    $batch_builder->setFinishCallback([static::class, 'batchFinished']);
    batch_set($batch_builder->toArray());

    return batch_process('/admin/config/media/cleaner/report');
  }

  /**
   * Batch operation callback to delete media items.
   *
   * @param array $media_ids
   *   Array of media IDs to delete.
   * @param array $context
   *   The batch context array.
   */
  public static function processMediaDeletion($media_ids, &$context) {
    // Initialize results if not set.
    if (!isset($context['results']['deleted'])) {
      $context['results']['deleted'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['not_found'] = 0;
      $context['results']['error_messages_shown'] = 0;
    }

    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $logger = \Drupal::logger('unused_media_cleaner');
    $messenger = \Drupal::messenger();

    foreach ($media_ids as $mid) {
      try {
        $media = $media_storage->load($mid);

        if (!$media) {
          $context['results']['not_found']++;
          $logger->warning('Media @mid not found', ['@mid' => $mid]);
          continue;
        }

        $media_label = $media->label();

        //  NO!! DO not delete files! they are not duplicated in the filesystem!!!!!!!!!!!!!!!!
        // Manual approach to handle different possible file fields.
        // $file_deleted = FALSE;
        // $possible_fields = [
        //   'field_media_file',
        //   'field_media_image',
        //   'field_media_document',
        //   'field_media_video_file',
        //   'field_media_scitalk_video',
        // ];

        // foreach ($possible_fields as $field_name) {
        //   if ($media->hasField($field_name) && !$media->get($field_name)->isEmpty()) {
        //     $file = $media->get($field_name)->entity;
        //     if ($file) {
        //       try {
        //         $file->delete();
        //         $file_deleted = TRUE;
        //         // Exit loop as soon as a file is found and deleted.
        //         break;
        //       }
        //       catch (\Exception $file_error) {
        //         $logger->error('Error deleting file for media @mid: @error', [
        //           '@mid' => $mid,
        //           '@error' => $file_error->getMessage(),
        //         ]);
        //         // Continue to try to delete the media entity even if
        //         // file deletion failed.
        //       }
        //     }
        //   }
        // }

        // Delete the media entity.
        $media->delete();
        $context['results']['deleted']++;

        $logger->info('Successfully deleted media @mid (@label)', [
          '@mid' => $mid,
          '@label' => $media_label,
        ]);

        // Update progress message.
        $context['message'] = t('Deleted media: @label (ID: @mid)', [
          '@label' => $media_label,
          '@mid' => $mid,
        ]);

      }
      catch (\Exception $e) {
        $context['results']['failed']++;

        $logger->error('Error deleting media @mid: @error', [
          '@mid' => $mid,
          '@error' => $e->getMessage(),
        ]);

        // Limit the number of error messages shown to avoid spamming.
        if ($context['results']['error_messages_shown'] < 5) {
          $messenger->addError(t('Error deleting media @mid: @error', [
            '@mid' => $mid,
            '@error' => $e->getMessage(),
          ]));
          $context['results']['error_messages_shown']++;
        }
      }
    }
  }
}
