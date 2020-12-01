<?php

namespace Drupal\scitalk_base\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique ID.
 *
 * @Constraint(
 *   id = "RepositoryShortNameMaxLength",
 *   label = @Translation("Repository Short Name length contraint", context = "Validation"),
 *   type = "entity"
 * )
 */
class RepositoryShortNameConstraint extends Constraint {

  // The message that will be shown if the value is not unique.
  public $tooLong = 'Repository short name must be 20 characters or less';

}
