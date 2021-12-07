<?php
  namespace Drupal\scitalk_base\Plugin\Field\FieldFormatter;

  use Drupal\Core\Field\FormatterBase;
  use Drupal\Core\Field\FieldItemListInterface;
  use Drupal\Core\Security\TrustedCallbackInterface;

  /**
   * Plugin implementation of the scitalk_most_recent_talk_formatter formatter.
   *
   * @FieldFormatter(
   *   id = "scitalk_most_recent_talk_formatter",
   *   module = "scitalk_base",
   *   label = @Translation("Display most recent Talk Date"),
   *   field_types = {
   *     "datetime"
   *   }
   * )
   */
  class SciTalkMostRecentTalkFormatter extends FormatterBase implements TrustedCallbackInterface {

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

        $elements[] = [
            '#lazy_builder' => [
              static::class . '::fetchMostRecentTalkDate',
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
     * return the date for the most recent talk under a Collection
     */
    public static function fetchMostRecentTalkDate($nid) {

        $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $bundle = $entity->bundle();

        //query most recent talk for a collection or series 
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'talk')
          ->condition('status', 1)
          ->condition('field_talk_collection.target_id', $nid);
        

        $talk = $query->sort('field_talk_date','DESC')
          ->range(0,1)
          ->execute();

        $most_recent_date = '';
        $date_format = 'Y-m-d';
        if ($talk) {
          $talkNID = current($talk);
          $talk_entity = \Drupal::entityTypeManager()->getStorage('node')->load($talkNID);
          
          $most_recent_date = $talk_entity->field_talk_date->value ?? '';

          if (!empty($most_recent_date)) {
            $tz = new \DateTimeZone( \Drupal::currentUser()->getTimezone());
            $utc = new \DateTimeZone("UTC");
            $most_recent_date =  (new \Drupal\Core\Datetime\DrupalDateTime($most_recent_date, $utc))->setTimezone($tz)->format($date_format);
          }
        }
        
        $markup = [
            '#markup' => $most_recent_date,
        ];
        return $markup;
    }

    /**
     * {@inheritDoc}
     */
    public static function trustedCallbacks() {
        // For security reasons we need to declare which methods on this class are
        // safe for use as a callback.
        return ['fetchMostRecentTalkDate'];
    }
  }