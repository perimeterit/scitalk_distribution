<?php
  namespace Drupal\scitalk_base\Plugin\Field\FieldFormatter;

  use Drupal\Core\Field\FormatterBase;
  use Drupal\Core\Field\FieldItemListInterface;
  use Drupal\Core\Security\TrustedCallbackInterface;

  /**
   * Plugin implementation of the scitalk_number_of_talks_formatter formatter.
   *
   * @FieldFormatter(
   *   id = "scitalk_number_of_talks_formatter",
   *   module = "scitalk_base",
   *   label = @Translation("Display number of Talks"),
   *   field_types = {
   *     "integer"
   *   }
   * )
   */
  class SciTalkNumberOfTalksFormatter extends FormatterBase implements TrustedCallbackInterface {

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
      $elements = [];
      
      foreach ($items as $delta => $item) {
        $entity_id = $item->getValue();
        if (is_array($entity_id)) {
          $entity_id = array_shift($entity_id);
        }

        // $elements[] = [
        //     '#lazy_builder' => [
        //       static::class . '::fetchNumberOfTalks',
        //       [
        //         $entity_id,
        //       ],
        //     ],
        //     '#create_placeholder' => TRUE,
        //     '#cache' => [
        //       'contexts' => [
        //         //'user',
        //         'url',
        //       ],
        //     ],
        //   ];

        //Drupal 9 integration issued this error:
        //    #lazy_builder value 'non-empty-string' at key '0' is invalid.
        //so replacing static::class with SciTalkNumberOfTalksFormatter::class seemed to fix the error
        $elements[] = [
          '#lazy_builder' => [
            SciTalkNumberOfTalksFormatter::class . '::fetchNumberOfTalks',
            [
              $entity_id,
            ],
          ],
          '#create_placeholder' => TRUE,
          '#cache' => [
            'contexts' => [
              //'user',
              'url',
            ],
          ],
        ];

      }

      return $elements;
    }

    /**
     * return the number of talks under a Collection or Series
     */
    public static function fetchNumberOfTalks($nid) {
        $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $bundle = $entity->bundle();
        
        //query number of talks for a collection
        $query_count = \Drupal::entityQuery('node')
          ->condition('type', 'talk')
          ->condition('status', 1)
          ->condition('field_talk_collection.target_id', $nid);

        $markup = [
            '#markup' => $query_count->count()->execute() ?? 0
        ];
        return $markup;
    }

    /**
     * {@inheritDoc}
     */
    public static function trustedCallbacks() {
        // For security reasons we need to declare which methods on this class are
        // safe for use as a callback.
        return ['fetchNumberOfTalks'];
    }
  }