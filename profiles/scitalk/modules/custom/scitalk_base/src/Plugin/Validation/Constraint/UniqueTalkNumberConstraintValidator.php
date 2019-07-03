<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueTalkNumber constraint.
 */
class UniqueTalkNumberConstraintValidator extends ConstraintValidator {
  private $entity_type = 'talk';

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    if (!isset($item)) {
      return;
    }
    $entity = $item->getEntity();
    if ($entity->bundle() == $this->entity_type) {
      $isUnique = true;

      // If the entity already has an id we're in an entity *update* operation
      //make sure that (in case they are changing the talk number) that no other entity has the same talk number
      if ($entity->id()) {
        $isUnique = $this->isUnique( $entity->field_talk_number->value, $entity->id() );
      }
      else {
        $isUnique = $this->isUnique( $entity->field_talk_number->value );
      }

      if (!$isUnique) {
        $this->context->addViolation( $constraint->notUnique, ['%value' => $entity->field_talk_number->value] );
      }
    }
  }

  /**
   * Is unique?
   *
   * @param string $value
   */
  private function isUnique($talk_number, $id = '') {
    $query_count = \Drupal::entityQuery('node')
         ->condition('type', $this->entity_type)
         ->condition('field_talk_number', $talk_number, '=')
         ->count();

    //for updates: check that no other entity has this talk number
    if (!empty($id)) {
      $query_count = \Drupal::entityQuery('node')
         ->condition('type', $this->entity_type)
         ->condition('field_talk_number', $talk_number, '=')
         ->condition('nid', $id, '<>')
         ->count();

    }

    return $query_count->execute() == 0;
  }

}
