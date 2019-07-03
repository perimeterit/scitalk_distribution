<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique ID.
 *
 * @Constraint(
 *   id = "UniqueTalkNumber",
 *   label = @Translation("Unique Talk Number is required", context = "Validation"),
 *   type = "entity"
 * )
 */
class UniqueTalkNumberConstraint extends Constraint {

  // The message that will be shown if the value is not unique.
  public $notUnique = 'Talk number %value is not unique';

}
