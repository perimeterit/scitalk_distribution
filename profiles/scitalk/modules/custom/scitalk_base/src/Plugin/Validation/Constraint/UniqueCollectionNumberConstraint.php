<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique Id.
 *
 * @Constraint(
 *   id = "UniqueCollectionNumber",
 *   label = @Translation("Unique Collection Number is required", context = "Validation"),
 *   type = "entity"
 * )
 */
class UniqueCollectionNumberConstraint extends Constraint {

  // The message that will be shown if the value is not unique.
  public $notUnique = 'Collection number %value is not unique';

}
