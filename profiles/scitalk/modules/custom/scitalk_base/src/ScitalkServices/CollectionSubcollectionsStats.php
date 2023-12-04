<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;

class CollectionSubcollectionsStats {
   
    /**
     * find the parent Collection(s) a Collection is part of and then update the number of sub collections for the parent Collection(s)
     */
    public function update(EntityInterface $entity) {
        $collections = $entity->get('field_parent_collection') ?? NULL;

         //if not attached to a collection now then check if it was previously attached to one and if so update
         if (empty($collections->getValue())) {
            $original_coll = $entity?->original ?? [];
            $prev_collections = $original_coll->field_parent_collection ?? NULL;
            $val = $prev_collections?->getValue() ?? NULL;
            if (!empty($val)) {
                //it was attached to a collection before, need to decrease the number of talks under the collection!
                $collections = $prev_collections;
            }
            else {
                return;
            }
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
            ->condition('field_parent_collection.target_id', $nid)
            ->accessCheck(TRUE);

        return  $query_count->count()->execute() ?? 0;
    }
}