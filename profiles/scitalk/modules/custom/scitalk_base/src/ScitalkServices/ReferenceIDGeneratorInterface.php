<?php

namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Interface ReferenceIDGeneratorInterface.
 */
interface ReferenceIDGeneratorInterface {

  public function generateReferenceId(EntityInterface $entity);
}
