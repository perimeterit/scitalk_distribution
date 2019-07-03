<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueCollectionNumber constraint.
 */
class UniqueCollectionNumberConstraintValidator extends ConstraintValidator { 
  private $vocabulary_name = 'collection';

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $taxonomy = $entity->getEntity(); 

    if (!isset($taxonomy)) {
      return;
    }

    if ($taxonomy->getVocabularyId() == $this->vocabulary_name) {
      $isUnique = true;
      // If the taxonomy already has an id we're in an entity *update* operation
      //make sure that (in case they are changing the talk number) that no other entity has the same colletion number
      if ($taxonomy->id()) {
        $isUnique = $this->isUnique( $taxonomy->field_collection_number->value, $taxonomy->id() );
      }
      else {
        $isUnique = $this->isUnique( $taxonomy->field_collection_number->value );
      }

      if (!$isUnique) {
        $this->context->addViolation( $constraint->notUnique, ['%value' => $taxonomy->field_collection_number->value] );
      }

    }
  }

  /**
   * Is unique?
   *
   * @param string $value
   */
  private function isUnique($value, $tid = '') {
    $query_count = \Drupal::entityQuery('taxonomy_term')
         ->condition('vid', $this->vocabulary_name)
         ->condition('field_collection_number', $value, '=')
         ->count();

    //for updates: check that no other taxonomy has this collection number
    if (!empty($tid)) {
      $query_count = \Drupal::entityQuery('taxonomy_term')
         ->condition('vid', $this->vocabulary_name)
         ->condition('field_collection_number', $value, '=')
         ->condition('tid', $tid, '<>')
         ->count();

    }

    return $query_count->execute() == 0;
  }

}
