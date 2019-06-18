<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\scitalk_base\ScitalkServices\ReferenceIDGeneratorInterface;
use Drupal\Core\Entity\EntityInterface;
  

class ReferenceIDGenerator implements ReferenceIDGeneratorInterface {
  
  public function generateReferenceId( EntityInterface $entity) {
      $type = strtolower( $entity->bundle() );
      $return_number = NULL;

      switch($type) {
        case 'talk':
          $return_number = $this->getNewReferenceValue($type);
          break;
  
        case 'collection':
        case 'series':
          $return_number = $this->getNewCollectionReferenceValue($type);
  
          break;
      }
      return $return_number;

  }

  //create talk number
  private function getNewReferenceValue($type) {

    $node_query = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('type', $type)
    ->sort('field_talk_number','DESC')
    ->range(0,1)   //get just 1
    ->execute();

    if ($node_query) {
      $nid = current($node_query);
      $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $reference_number = $entity->field_talk_number->value;    //$entity->get('field_talk_number')->getValue();
      $new_reference_number = $reference_number + 1;
      $new_reference_number = str_pad($new_reference_number, 6, "0", STR_PAD_LEFT);

      \Drupal::logger('scitalk_base')->notice('<pre><code>SciTalk New Reference number created for Content Type ' . $type . ': ' . print_r($reference_number . ' => ' . $new_reference_number, TRUE) . '</code></pre>');
      return $new_reference_number;
    }
      
    $last_number = 1;
    $new_reference_number = str_pad($last_number, 6, "0", STR_PAD_LEFT);

    \Drupal::logger('scitalk_base')->notice('<pre><code>SciTalk New Reference number created for Content Type ' . $type . ':' . print_r( $new_reference_number, TRUE) . '</code></pre>');

    return $new_reference_number;

  }

  //create a collection or series number (based on the value of $type)
  private function getNewCollectionReferenceValue($type) {
    $vocabulary_name = $type;
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary_name)
      ->sort('tid', 'DESC')
      ->range(0,1);   //get just 1

    $tids = $query->execute();

    if ($tids) {
      $tid = current($tids);
      
      $last_number = $tid + 1; 
      $return_number = strtoupper( substr($type, 0, 1) ) .  str_pad($last_number, 5, "0", STR_PAD_LEFT);

      \Drupal::logger('pirsa_base')->notice('<pre><code>New Collection number created : ' . print_r( $return_number, TRUE) . '</code></pre>');
      return $return_number;
    }

    //if first ever value then start from 1
    $last_number = 1;
    $return_number = strtoupper( substr($type, 0, 1) ) .  str_pad($last_number, 5, "0", STR_PAD_LEFT);

    \Drupal::logger('scitalk_base')->notice('<pre><code> New Collection number created : ' . print_r( $return_number, TRUE) . '</code></pre>');
    return $return_number;
  }

}
