<?php

/**
 * @file
 *  SciTalk site profile code.
 */

 /**
 * Implements hook_form_alter().
 */
function scitalk_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  /* @var Drupal\Core\Entity\FieldableEntityInterface $entity */
  $formObject = $form_state->getFormObject();
  if ($formObject instanceof \Drupal\Core\Entity\EntityFormInterface) {
    $entity = $formObject->getEntity();
    \Drupal::logger('scitalk')->notice('in FORM ALTER: "%entID", "%bundle"', array('%entID' => $entity->getEntityTypeId(), '%bundle' => $entity->bundle()));
    if ($entity->getEntityTypeId() === 'contact_message'  && in_array($entity->bundle(), ['scitalk_feedback'])) {
         $form['#attached']['library'][] = 'scitalk/scitalk_profile_js';
    }
  }
}

