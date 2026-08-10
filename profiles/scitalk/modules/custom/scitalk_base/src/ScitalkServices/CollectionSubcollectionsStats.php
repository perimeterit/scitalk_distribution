<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;

class CollectionSubcollectionsStats {
    protected $updatedCollections = []; // Keep track of collections that have already been updated to avoid duplicate updates
   
    /**
     * find the parent Collection(s) a Collection is part of and then update the number of sub collections for the parent Collection(s)
     */
    public function update(EntityInterface $entity) {
        $collections = [];

        // update current collection's parent if any
        $collection = $entity->field_parent_collection ?? NULL;
        if (!empty($collection?->getValue())) {
            if (!isset($this->updatedCollections[$collection->target_id])) {
                $collections[] = $collection;
                $this->updatedCollections[$collection->target_id] = TRUE;
            }
        }
        
        // find previous collection's parent if any and add to the list of collections to update
        $original_coll = $entity?->original ?? NULL;
        $prev_collections = $original_coll?->field_parent_collection ?? NULL;
        $val = $prev_collections?->getValue() ?? NULL;
        if (!empty($val)) {
            if (!isset($this->updatedCollections[$prev_collections->target_id])) {
                //it was attached to a collection before, need to decrease the number of talks under the collection!
                $collections[] = $prev_collections;
                $this->updatedCollections[$prev_collections->target_id] = TRUE;
            }
        }

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
    public function fetchNumberOfSubcollections(string $nid) {
        //query number of talks for a collection
        $query_count = \Drupal::entityQuery('node')
            ->condition('type', 'collection')
            ->condition('status', 1)
            ->condition('field_parent_collection.target_id', $nid)
            ->accessCheck(TRUE);

        return  $query_count->count()->execute() ?? 0;
    }
}