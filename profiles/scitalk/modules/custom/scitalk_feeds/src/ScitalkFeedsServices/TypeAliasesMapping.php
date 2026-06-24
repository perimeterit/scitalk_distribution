<?php
namespace Drupal\scitalk_feeds\ScitalkFeedsServices;

use Drupal\taxonomy\Entity\Term;

class TypeAliasesMapping {
    public function __construct() {}
    public function getCollectionTypeAliasesMapping(): array {
        $mapping = &drupal_static(__FUNCTION__);
        if ($mapping == NULL) {
            $mapping = $this->getAliasesMapping('collection_type');
        }
        return $mapping;
    }

    public function getTalkTypeAliasesMapping(): array {
        $mapping = &drupal_static(__FUNCTION__);
        if ($mapping == NULL) {
            $mapping = $this->getAliasesMapping('talk_type');
        }
        return $mapping;
    }

    private function getAliasesMapping(string $type): array {
        $mapping = [];
        $collection_ids = \Drupal::entityQuery('taxonomy_term')
            ->condition('status', 1)
            ->condition('vid', $type)
            ->accessCheck(TRUE)
            ->execute();

        $collections = Term::loadMultiple($collection_ids);
        foreach ($collections as $collection) {
            $name = $collection->name->value;
            $aliases = $collection->hasField('field_aliases') ? $collection?->get('field_aliases')?->getValue() : [];
            if (!empty($aliases)) {
                foreach ($aliases as $alias) {
                    $mapping[$alias['value']] = $name;
                }
            }
        }
        return $mapping;
    }
}