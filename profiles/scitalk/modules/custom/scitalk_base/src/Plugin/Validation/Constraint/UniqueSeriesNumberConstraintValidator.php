<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueSeriesNumber constraint.
 */
class UniqueSeriesNumberConstraintValidator extends ConstraintValidator { 
  private $vocabulary_name = 'series';

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    //Determine what entity type this is.
    $thisEntity = $entity->getEntity();
    
    if (!isset($thisEntity)) {
      return;
    }
    
    $entityType = $thisEntity->getEntityTypeId();
    
    if($entityType == 'node') {
      //for the node collection type, load the source and talk
      $source_target_id = $thisEntity->get('field_series_source')->target_id ?? 0;
      $source_field = \Drupal::entityTypeManager()->getStorage('node')->load($source_target_id);
      $source_name = $source_field->title->value ?? '';
      
      //Make sure that (in case they are changing the talk number) that no other entity has the same colletion number
      if ($thisEntity->id()) {
        $isUnique = $this->isUnique( $thisEntity->field_series_number->value, $source_name, $thisEntity->id() );
      }
      else {
        $isUnique = $this->isUnique( $thisEntity->field_series_number->value, $source_name);
      }
      
      if (!$isUnique) {
        $this->context->addViolation( $this->t($constraint->notUnique, ['%value' => $thisEntity->field_series_number->value]) );
      }
    }
    elseif($entityType == 'taxonomy') {
      if ($thisEntity->getVocabularyId() == $this->vocabulary_name) {
        $isUnique = true;
        
        //new requirement: unique is now a talk number + source
        $source_target_id = $thisEntity->get('field_collection_source')->target_id ?? 0;
        $source_field = \Drupal::entityTypeManager()->getStorage('node')->load($source_target_id);
        $source_name = $source_field->title->value ?? '';
        
        // If the taxonomy already has an id we're in an entity *update* operation
        //make sure that (in case they are changing the talk number) that no other entity has the same colletion number
        if ($thisEntity->id()) {
          $isUnique = $this->isUnique( $thisEntity->field_collection_number->value, $source_name, $thisEntity->id() );
        }
        else {
          $isUnique = $this->isUnique( $thisEntity->field_collection_number->value, $source_name);
        }
        
        if (!$isUnique) {
          $this->context->addViolation( $this->t($constraint->notUnique, ['%value' => $thisEntity->field_collection_number->value]) );
        }
        
      }
    }
  }

  /**
   * Is unique?
   *
   * @param string $value
   */
  private function isUnique($value, $source_name, $tid = '') {
    
    
    
    if($entityType == 'taxonomy') {
      $query_count = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $this->vocabulary_name)
      ->condition('field_collection_number', $value, '=');
      
      if (empty($source_name)) {
        $query_count->notExists('field_series_source.entity.title');
      }
      else {
        $query_count->condition('field_series_source.entity.title', $source_name);
      }
      
      if (!empty($tid)) {
        $query_count->condition('tid', $tid, '<>');
      }
    }
    else {
      $query_count = \Drupal::entityQuery('node')
      ->condition('type', 'collection')
      ->condition('field_collection_number', $value, '=');
      
      if (empty($source_name)) {
        $query_count->notExists('field_series_source.entity.title');
      }
      else {
        $query_count->condition('field_series_source.entity.title', $source_name);
      }
      
      if (!empty($tid)) {
        $query_count->condition('tid', $tid, '<>');
      }
      
    }
    $query_count->count();
    
    return $query_count->execute() == 0;
    
  }

}
