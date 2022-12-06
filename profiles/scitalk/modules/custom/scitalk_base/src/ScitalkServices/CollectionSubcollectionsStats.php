<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;

class CollectionSubcollectionsStats {
   
    /**
     * find the parent Collection(s) a Collection is part of and then update the number of sub collections for the parent Collection(s)
     */
    public function update(EntityInterface $entity) {
        $collections = $entity->get('field_parent_collection') ?? NULL;

        if (empty($collections)) {
            return;
        }

        foreach ($collections as $coll) {
            $collection_nid = $coll->target_id ?? '';

            $number_of_subcollections = $this->fetchNumberOfSubcollections($collection_nid);

            $collection = \Drupal::entityTypeManager()->getStorage('node')->load($collection_nid);
            if (!empty($collection)) {
                $collection->set('field_collection_number_children', $number_of_subcollections);

                $collection->save();
            }
        }
    }

     /**
     * return the number of Subcollections under a Collection or Series
     */
    public function fetchNumberOfSubcollections($nid) {
        //query number of talks for a collection
        $query_count = \Drupal::entityQuery('node')
            ->condition('type', 'collection')
            ->condition('status', 1)
            ->condition('field_parent_collection.target_id', $nid);

        return  $query_count->count()->execute() ?? 0;
    }
}