<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;

class CollectionTalksStats {
   
    public function update(EntityInterface $entity) {
        $collection_nid = $entity->get('field_talk_collection')->target_id ?? '';

        if (empty($collection_nid)) {
            return;
        }

        $number_of_talks = $this->fetchNumberOfTalks($collection_nid);
        $most_recent_talk = $this->fetchMostRecentTalkDate($collection_nid);

        $collection = \Drupal::entityTypeManager()->getStorage('node')->load($collection_nid);
        $collection->set('field_collection_number_of_talks', $number_of_talks);
        $collection->set('field_collection_last_talk_date', $most_recent_talk);

        $collection->save();
    }

    /**
     * return the number of talks under a Collection or Series
     */
    private function fetchNumberOfTalks($nid) {
        $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $bundle = $entity->bundle();
        
        //query number of talks for a collection
        $query_count = \Drupal::entityQuery('node')
            ->condition('type', 'talk')
            ->condition('status', 1)
            ->condition('field_talk_collection.target_id', $nid);

        return  $query_count->count()->execute() ?? 0;
    }

     /**
    * return the date for the most recent talk under a Collection
    */
   private static function fetchMostRecentTalkDate($nid) {
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
        $date_format = 'Y-m-d\TH:i:s';
        if ($talk) {
            $talk_nid = current($talk);
            $talk_entity = \Drupal::entityTypeManager()->getStorage('node')->load($talk_nid);
            
            $most_recent_date = $talk_entity->field_talk_date->value ?? '';

            // if (!empty($most_recent_date)) {
            //     $tz = new \DateTimeZone( \Drupal::currentUser()->getTimezone());
            //     $utc = new \DateTimeZone("UTC");
            //     $most_recent_date =  (new \Drupal\Core\Datetime\DrupalDateTime($most_recent_date, $utc))->setTimezone($tz)->format($date_format);
            // }
        }

        return $most_recent_date;
    }
}