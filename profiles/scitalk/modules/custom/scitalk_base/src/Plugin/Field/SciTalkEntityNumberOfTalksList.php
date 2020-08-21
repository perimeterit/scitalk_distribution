<?php

namespace Drupal\scitalk_base\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

class SciTalkEntityNumberOfTalksList extends FieldItemList implements FieldItemListInterface {
  
  use ComputedItemListTrait;

  
  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    $bundle = $entity->bundle();
 
    $this->list[0] = $this->createItem(0, $entity->id());
  }
}