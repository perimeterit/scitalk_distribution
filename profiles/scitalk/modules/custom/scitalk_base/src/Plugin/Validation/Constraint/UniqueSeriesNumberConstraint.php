<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique ID.
 *
 * @Constraint(
 *   id = "UniqueSeriesNumber",
 *   label = @Translation("Unique Series Number is required", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueSeriesNumberConstraint extends Constraint {

  // The message that will be shown if the value is not unique.
  public $notUnique = 'Series number %value is not unique';

}
