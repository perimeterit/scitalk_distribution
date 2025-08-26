<?php

namespace Drupal\scitalk_unused_media\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\unused_media_cleaner\Form\MediaReportForm;


/**
 * Form for generating and displaying the unused media report.
 * Using the code from the "unused_media_cleaner" contrib module and adapting it for us:
 *  Instead of deleting big files, we are deleting unused media
 *  So we don't need the size_threshold form element and the csv report button
 */
class SciTalkMediaReportForm extends MediaReportForm {


    /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Top container for description and actions.
    $form['top'] = [
      '#type' => 'container',
      '#weight' => -100,
    ];

    // Description for the report.
    $form['top']['description'] = [
      '#markup' => $this->t('Click the button to generate a report of all media files that are not being used.'),
    ];

    // we don't need this element *****************************************************************
    // Size threshold selector.
    // $form['top']['size_threshold'] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Size threshold'),
    //   '#options' => array_combine(
    //     range(1, 10),
    //     array_map(function ($size) {
    //       return $size . ' Mo';
    //     }, range(1, 10))
    //   ),
    //   '#default_value' => 5,
    //   '#required' => TRUE,
    // ];

    // Actions container.
    $form['top']['actions']['#type'] = 'actions';
    $form['top']['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Report'),
      '#button_type' => 'primary',
    ];

    // Get count of unused media from stored data.
    $unused_count = $this->getUnusedMediaCount();

    // Add delete unused media button if there are unused items.
    if ($unused_count > 0) {
      $form['top']['actions']['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete Unused Media (@count)', ['@count' => $unused_count]),
        '#url' => Url::fromRoute('unused_media_cleaner.delete_unused'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
          'onclick' => 'return confirm("' . $this->t('Are you sure you want to delete all unused media? This action cannot be undone.') . '")',
        ],
      ];
    }

    // we don't need this element *****************************************************************
    // Add export CSV button if report data exists.
    // $stored_data = $this->state->get('unused_media_cleaner.report_data', []);
    // if (!empty($stored_data)) {
    //   $form['top']['actions']['export_csv'] = [
    //     '#type' => 'link',
    //     '#title' => $this->t('Export to CSV'),
    //     '#url' => Url::fromRoute('unused_media_cleaner.export_csv'),
    //     '#attributes' => [
    //       'class' => ['button', 'button--secondary'],
    //       'target' => '_blank',
    //     ],
    //   ];
    // }

    // Add table of results if report data exists.
    $report_data = $this->state->get('unused_media_cleaner.report_data', []);
    if (!empty($report_data)) {
      $form['previous_report'] = [
        '#type' => 'details',
        '#title' => $this->t('Report Results'),
        '#open' => TRUE,
        'summary' => [
          '#markup' => $this->t('Total media items found: @count', ['@count' => count($report_data)]),
        ],
        'table' => [
          '#type' => 'table',
          '#header' => [
            $this->t('Media Name'),
            $this->t('ID'),
            $this->t('Current Size'),
            $this->t('Estimated WebP Size'),
            $this->t('Usage Count'),
            $this->t('Usage URLs'),
          ],
          '#empty' => $this->t('No report data available'),
          '#rows' => $this->formatReportData($report_data),
        ],
      ];
    }

    return $form;
    
  }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear previous report data.
    $this->state->delete('unused_media_cleaner.report_data');

    // Get the selected threshold from form values and convert to bytes.
    $threshold = $form_state->getValue('size_threshold') * 1024 * 1024;

    // Query all media IDs using the entity type manager.
    $mids = $this->entityTypeManager->getStorage('media')
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort('mid', 'ASC')
      ->execute();

    // Split media IDs into chunks for batch processing.
    $chunks = array_chunk($mids, 50);

    // Set up the batch process.
    $batch = (new BatchBuilder())
      ->setTitle($this->t('Generating media report'))
      ->setInitMessage($this->t('Starting analysis...'))
      ->setProgressMessage($this->t('Processed chunk @current of @total'))
      ->setErrorMessage($this->t('An error occurred during analysis.'))
      ->setFinishCallback([self::class, 'batchFinished']);

    foreach ($chunks as $chunk) {
      $batch->addOperation([self::class, 'processChunk'], [$chunk, $threshold]);
    }

    batch_set($batch->toArray());
  }


  /**
   * Batch operation to process a chunk of media entities.
   *
   * @param array $chunk
   *   Array of media IDs.
   * @param int $threshold
   *   Size threshold in bytes.
   * @param array $context
   *   Batch context array.
   */
  public static function processChunk(array $chunk, int $threshold, array &$context) {
    try {
      // Initialize context results if not set.
      if (!isset($context['results']['rows'])) {
        $context['results']['rows'] = [];
      }

      $media_storage = \Drupal::entityTypeManager()->getStorage('media');
      foreach ($chunk as $mid) {
        $media = $media_storage->load($mid);
        if (!$media) {
          continue;
        }

        // Check if the media entity has a file field and get the file.
        $file = NULL;
        if ($media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
          $file = $media->get('field_media_image')->entity;
        }
        elseif ($media->hasField('field_media_file') && !$media->get('field_media_file')->isEmpty()) {
          $file = $media->get('field_media_file')->entity;
        }
      
        // we don't need this element *****************************************************************
        // if (!$file || $file->getSize() <= $threshold) {
        //   continue;
        // }

        // Find usages of the media entity.
        $usages = self::findMediaUsages($mid);
        $used_on = !empty($usages) ? implode(' | ', $usages) : 'Not used';
        if (!empty($usages)) {
            continue;
        }

        // Store the row data in a structured format.
        $context['results']['rows'][] = [
          'name' => (string) $media->label() . ' (' . $media->bundle() . ')',
          'id' => $mid,
          'size' => $file?->getSize() ? round($file->getSize() / (1024 * 1024), 2) . ' Mo' : 0,
          'used_on' => $used_on,
        ];
      }

      // Update the total processed count.
      if (!isset($context['sandbox']['processed'])) {
        $context['sandbox']['processed'] = 0;
      }
      $context['sandbox']['processed'] += count($chunk);
      $context['finished'] = empty($context['sandbox']['total']) ? 1 : ($context['sandbox']['processed'] / $context['sandbox']['total']);
    }
    catch (\Exception $e) {
      \Drupal::logger('unused_media_cleaner')->error($e->getMessage());
      $context['success'] = FALSE;
      $context['finished'] = 1;
    }
  }

}
