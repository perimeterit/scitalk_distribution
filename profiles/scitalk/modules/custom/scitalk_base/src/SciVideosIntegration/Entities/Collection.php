<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Entities;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;

class Collection extends EntityBase {
    private const COLLECTION_PATH = 'api/node/collection';

    public function __construct(SciVideosAuthentication $scivideos) {
        parent::__construct($scivideos, Collection::COLLECTION_PATH);
    }

    /**
     * fetch a Collection by $collection_number
     */
    public function fetchByCollectionNumber($collection_number) {
        $filter = '?filter[field_collection_number][value]=' . $collection_number;
        return parent::fetch($filter);
    }

    /**
     * fetch the collections a Collection with $collection_id (uuid) is a parent of (i.e. get a Collection's children)
     */
    public function fetchCollectionChildrenById($collection_id) {
        $filter = '?filter[parent_collection][condition][path]=field_parent_collection.id&filter[parent_collection][condition][operator]==&filter[parent_collection][condition][value]=' . $collection_id;
        return parent::fetch($filter);
    }
}