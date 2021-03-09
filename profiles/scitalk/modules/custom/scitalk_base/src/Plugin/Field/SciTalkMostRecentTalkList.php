<?php

namespace Drupal\scitalk_base\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;


class SciTalkMostRecentTalkList extends FieldItemList implements FieldItemListInterface {
  
  use ComputedItemListTrait;

  
  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
   //xdebug_break();
    $entity = $this->getEntity();
    $bundle = $entity->bundle();

    $entity_view_id = "node.{$bundle}.default";
    $view_mode = \Drupal::entityManager()->getStorage('entity_view_display')->load($entity_view_id);
    //$visible_fields = $view_mode ? $view_mode->get('content') : [];
    $hidden_fields = $view_mode ? $view_mode->get('hidden') : [];
    
    //only compute if this field is enabled/visible in the default entity view display for series or collections:
    if (!in_array('scitalk_most_recent_talk', array_keys($hidden_fields))) {
      $this->list[0] = $this->createItem(0, $entity->id());
    }
  }
}