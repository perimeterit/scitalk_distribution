<?php

namespace Drupal\scitalk_base\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

class SciTalkPrefixedTalkNumberList extends FieldItemList implements FieldItemListInterface {

    use ComputedItemListTrait;


    /**
     * {@inheritdoc}
     */
    protected function computeValue() {
        $entity = $this->getEntity();
        $val = $this->prefixTalkNumber($entity);
        $this->list[0] = $this->createItem(0, $val);
    }

    /**
     * return the prefixed Talk Number
     */
    private function prefixTalkNumber($entity) {
        $talk_prefix = \Drupal::service('scitalk_base.talk_prefix')->get($entity);
        return $talk_prefix . ($entity->field_talk_number->value ?? '');
    }
}