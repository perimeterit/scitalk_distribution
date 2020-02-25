<?php
use Symfony\Component\HttpFoundation\Request;

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
    if ($entity->getEntityTypeId() === 'contact_message'  && in_array($entity->bundle(), ['scitalk_feedback'])) {

      //need to pass the referer page to the feedback form:
      $request = \Drupal::request();
      $referer = $request->headers->get('referer');

      // Getting the base url.
      $base_url = Request::createFromGlobals()->getSchemeAndHttpHost();
      // Getting the alias or the relative path.
      $referer_alias = substr($referer, strlen($base_url));
      
      $form['#attached']['library'][] = 'scitalk/scitalk_profile_js';
      $form['#attached']['drupalSettings']['scitalk']['scitalk_profile_js']['feedback_referer'] = $referer_alias;
    }
  }
  
}