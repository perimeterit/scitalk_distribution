<?php

namespace Drupal\scitalk_base\Plugin\FieldItemList;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;


class SciTalkEntityComputedText extends FieldItemList implements FieldItemListInterface {
  
  use ComputedItemListTrait;

  
  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $this->list[0] = $this->createItem(0, 'This is any value we want');
  }
  
  
  
}