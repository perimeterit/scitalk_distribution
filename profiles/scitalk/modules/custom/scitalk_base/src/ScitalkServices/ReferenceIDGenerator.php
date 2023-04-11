<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\scitalk_base\ScitalkServices\ReferenceIDGeneratorInterface;
use Drupal\Core\Entity\EntityInterface;
  

class ReferenceIDGenerator implements ReferenceIDGeneratorInterface {
  
  public function generateReferenceId( EntityInterface $entity) {

    $type = strtolower( $entity->bundle() );
    $return_number = NULL;
    $source_name = $this->getSourceName($entity);

    switch($type) {
      case 'talk':
        $talk_date = $entity->get('field_talk_date')->value ?? date('Y-m-d');
        $year = date('y', strtotime($talk_date));
        $month = date('m', strtotime($talk_date));

        $return_number = $this->getNewReferenceValue($type, $source_name, $year, $month);
        break;

      case 'collection':
        $return_number = $this->getNewCollectionReferenceValue($type, $source_name);

        break;
    }
    return $return_number;

  }

  //create talk number
  private function getNewReferenceValue($type, $source_name, $year, $month) {
    //create talk numbers with pattern of YYMM#### (year, month and running number padded with 0s), e.g. 23040001
    $prefix = str_pad($year, 2, "0", STR_PAD_LEFT) . str_pad($month, 2, "0", STR_PAD_LEFT);
    $prefix_search = $prefix . '%';

    $node_query = \Drupal::entityQuery('node');
    $node_query
      // ->condition('status', 1)
      ->condition('type', $type)
      ->condition('field_talk_number', $prefix_search, 'LIKE')
      ;

    if (empty($source_name)) {
      $node_query->notExists('field_talk_source_repository.entity.label');
    }
    else {
      $node_query->condition('field_talk_source_repository.entity.label', $source_name);
    }

    $result = $node_query->sort('field_talk_number','DESC')
     ->range(0,1)   //get just 1
     ->execute();

    if ($result) {
      $nid = current($result);

      $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $reference_number = $entity->field_talk_number->value;    //$entity->get('field_talk_number')->getValue();
      $new_reference_number = $reference_number + 1;
      $new_reference_number = str_pad($new_reference_number, 6, "0", STR_PAD_LEFT);

      \Drupal::logger('scitalk_base')->notice('New %source talk number "%number" created', array('%source' => $source_name, '%number'  => $new_reference_number, '%type' => $type));
      return $new_reference_number;
    }
      
    $last_number = 1;
    $new_reference_number = $prefix  . str_pad($last_number, 4, "0", STR_PAD_LEFT);

    \Drupal::logger('scitalk_base')->notice('New %source talk number "%number" created', array('%source' => $source_name, '%number'  => $new_reference_number, '%type' => $type));

    return $new_reference_number;

  }

  //create a collection number (based on the value of $type)
  private function getNewCollectionReferenceValue($type, $source_name) {
    $query = \Drupal::entityQuery('node')->condition('type', 'collection');
    $source_field = 'field_collection_source_repo.entity.label';

    if (empty($source_name)) {
      $query->notExists($source_field);
    }
    else {
      $query->condition($source_field, $source_name);
    }
    $query->sort('field_collection_number', 'DESC')->range(0,1);   //get just 1

    $entityIDs = $query->execute();
    if ($entityIDs) {
      $eid = current($entityIDs);
      $exists = TRUE;
      while($exists) {
        //let's use the collection last number instead of the tid to generate the next collection number
        $last_collection_number = (\Drupal::entityTypeManager()->getStorage('node')->load($eid))->field_collection_number->value;
        $last_collection_number_int_val = intval(substr($last_collection_number,1));  //the first char is either 'C' or 'S', the rest is the number
        $last_collection_number_int_val += 1;
        $return_number = strtoupper( substr($type, 0, 1) ) .  str_pad($last_collection_number_int_val, 5, "0", STR_PAD_LEFT);
        //now query if this return number exists
        $cquery = \Drupal::entityQuery('node')->condition('type', 'collection');
        $cquery->condition('field_collection_number', $return_number);
        if (!empty($source_name)) {
          $cquery->condition($source_field, $source_name);
        }
        $cquery->sort('field_collection_number', 'DESC')->range(0,1);   //get just 1
        $entityIDs = $cquery->execute();
        if($entityIDs) {
          $eid = current($entityIDs);
        }
        else {
          $exists = FALSE;
        }
      }
      \Drupal::logger('scitalk_base')->notice('New %source "%number" created', array('%source' => $source_name, '%number'  => $return_number));
      return $return_number;
          
    }

    //if first ever value then start from 1
    $last_number = 1;
    $return_number = strtoupper( substr($type, 0, 1) ) .  str_pad($last_number, 5, "0", STR_PAD_LEFT);
    //now test for this number existing
    $cquery = \Drupal::entityQuery('node')->condition('type', 'collection');
    $cquery->condition('field_collection_number', $return_number);
    $cquery->sort('field_collection_number', 'DESC')->range(0,1);   //get just 1
    $entityIDs = $cquery->execute();
    if ($entityIDs) {
      $eid = current($entityIDs);
      $exists = TRUE;
      while($exists) {
        $last_collection_number = (\Drupal::entityTypeManager()->getStorage('node')->load($eid))->field_collection_number->value;
        $last_collection_number_int_val = intval(substr($last_collection_number,1));  //the first char is either 'C' or 'S', the rest is the number
        $last_collection_number_int_val += 1;
        $return_number = strtoupper( substr($type, 0, 1) ) .  str_pad($last_collection_number_int_val, 5, "0", STR_PAD_LEFT);
      
        $cquery = \Drupal::entityQuery('node')->condition('type', 'collection');
        $cquery->condition('field_collection_number', $return_number);
        if (!empty($source_name)) {
          $cquery->condition($source_field, $source_name);
        }
        $cquery->sort('field_collection_number', 'DESC')->range(0,1);   //get just 1
        $entityIDs = $cquery->execute();
        if($entityIDs) {
          $eid = current($entityIDs);
        }
        else {
          $exists = FALSE;
        }
      }
    
    }
    
    \Drupal::logger('scitalk_base')->notice('New %source "%number" created', array('%source' => $source_name, '%number'  => $return_number));
    return $return_number;
  }

  //get source name for this talk, collection or series
  private function getSourceName(EntityInterface $entity) {
    $type = strtolower( $entity->bundle() );
    $source_target_id = 0;

    switch($type) {
      case 'talk':
        $source_target_id = $entity->get('field_talk_source_repository')->target_id ?? 0;
        break;

      case 'collection':
        $source_target_id = $entity->get('field_collection_source_repo')->target_id ?? 0;
        break;

    }

    $source_field = \Drupal::entityTypeManager()->getStorage('group')->load($source_target_id);
    return $source_field->label->value ?? '';
  }

}