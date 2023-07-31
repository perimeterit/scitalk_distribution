<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Entities;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;

class Collection extends EntityBase {
    private const COLLECTION_PATH = 'api/node/collection';

    public function __construct(SciVideosAuthentication $scivideos) {
        parent::__construct($scivideos, Collection::COLLECTION_PATH);
    }

    public function fetchByCollectionNumber($collection_number) {
        $filter = '?filter[field_collection_number][value]=' . $collection_number;
        return parent::fetch($filter);
    }
}