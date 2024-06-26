<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueCollectionNumber constraint.
 */
class UniqueCollectionNumberConstraintValidator extends ConstraintValidator {

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
      $source_target_id = $thisEntity->get('field_collection_source_repo')->target_id ?? 0;
      $source_field = \Drupal::entityTypeManager()->getStorage('node')->load($source_target_id);
      $source_name = $source_field->title->value ?? '';

      //Make sure that (in case they are changing the talk number) that no other entity has the same colletion number
      if ($thisEntity->id()) {
        $isUnique = $this->isUnique( $thisEntity->field_collection_number->value, $source_name, $thisEntity->id());
      }
      else {
        $isUnique = $this->isUnique( $thisEntity->field_collection_number->value, $source_name);
      }

      if (!$isUnique) {
        $this->context->addViolation( t($constraint->notUnique, ['%value' => $thisEntity->field_collection_number->value]) );
      }
    }

  }

  /**
   * Is unique?
   *
   * @param string $value
   */
  private function isUnique($value, $source_name, $id = '') {
    if (is_null($value)) {
      return TRUE;
    }

    $query_count = \Drupal::entityQuery('node')
      ->condition('type', 'collection')
      //->condition('status', 1)  //omitting as there could be a valid collection that is not published
      ->condition('field_collection_number', $value, '=');

    if (!empty($source_name)) {
      $query_count->condition('field_collection_source_repo.entity.label', $source_name);
    }

    if (!empty($id)) {
      $query_count->condition('nid', $id, '<>');
    }

    $query_count->accessCheck(TRUE);
    $query_count->count();
    return $query_count->execute() == 0;
  }

}
