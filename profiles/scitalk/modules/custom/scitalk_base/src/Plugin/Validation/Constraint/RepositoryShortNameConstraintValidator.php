<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Repository Short Name constraint.
 */
class RepositoryShortNameConstraintValidator extends ConstraintValidator {
  const MAX_LEN = 20;
  private $entity_type = 'source_repository';

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    if (!isset($item)) {
      return;
    }
    $entity = $item->getEntity();
    if ($entity->bundle() == $this->entity_type) {
      
      $short_repo_name_len = strlen($item->value) ?? 0;
      if ($short_repo_name_len > self::MAX_LEN) {
        $this->context->addViolation( t($constraint->tooLong) );
      }
    }
  }

}
