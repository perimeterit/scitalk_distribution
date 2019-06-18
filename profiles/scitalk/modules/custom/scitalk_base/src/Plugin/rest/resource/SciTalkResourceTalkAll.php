<?php
namespace Drupal\scitalk_base\Plugin\rest\resource;
/*
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\ResourceInterface;
use Drupal\rest\ResourceResponse;
*/
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Psr\Log\LoggerInterface;

/**
  * Provides a Resource to get and patch SciTalks
  *
  * @RestResource(
  *   id = "scitalk_talk_all",
  *   label = @Translation("SciTalk Talk REST API - GET list of Talks"),
  *   entity_type = "node",
  *   serialization_class = "Drupal\node\Entity\Node",
  *   uri_paths = {
  *     "canonical" = "/api/talk"
  *   }
  * )
  */

class SciTalkResourceTalkAll extends EntityResource {
  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $link_relation_type_manager
   *   The link relation type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, array $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, PluginManagerInterface $link_relation_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $serializer_formats, $logger, $config_factory, $link_relation_type_manager);

  }

  public function get(EntityInterface $entity = NULL, Request $request) {
   $response_array = [];
   $type = 'talk';

   $node_query = \Drupal::entityQuery('node')
     ->condition('status', 1)
     ->condition('type', $type)
     ->sort('changed', 'DESC')
     ->range(0, 100)
     ->accessCheck(FALSE)
     ->execute();

   if ($node_query) {
     $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($node_query);

     $no_talk_number_set = 0;
     foreach ($nodes as $entity)
     {
       $entity_access = $entity->access('view', NULL, TRUE);
       if (!$entity_access->isAllowed()) {
         //throw new CacheableAccessDeniedHttpException($entity_access, $entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'view'));
         //throw new CacheableAccessDeniedHttpException($entity_access, $this->generateFallbackAccessDeniedMessage($entity, 'view'));
         throw new AccessDeniedHttpException( $this->generateFallbackAccessDeniedMessage($entity, 'view'));
       }
       $this->logger->notice('entity %e is an instance of fieldbaleInterface %in',['%e' => $entity->bundle(), '%in'=> ($entity instanceof FieldableEntityInterface ? 'YES': 'NO') ]);
       if ($entity instanceof FieldableEntityInterface) {
        foreach ($entity as $field_name => $field) {
          /** @var \Drupal\Core\Field\FieldItemListInterface $field */
          $field_access = $field->access('view', NULL, TRUE);
        //  $response->addCacheableDependency($field_access);
        $this->logger->notice('Field name %f has access: %access.', ['%f' => $field_name, '%access' => ($field_access->isAllowed() ? 'YES' : 'NO') ]);
          if (!$field_access->isAllowed()) {
            $entity->set($field_name, NULL);
          }
        }
       }
       
       $talk_number = $entity->field_talk_number->value ?? $no_talk_number_set++;
       $response_array[$talk_number] = $entity;

     }


     //$response_array[] = $nodes;

/*     //checking each node access:
      foreach ($nodes as $node)
      {
        $entity_access = $node->access('view', NULL, TRUE);
        $access = $entity_access->isAllowed() ? ' YES' : ' NO';
        $response_array[] = ['title' => $node->title->value, 'has_access' => $access];
      }
*/

/*
     foreach ($nodes as $node) {
       $response_array[] = [
         'title' => $node->title->value,
       ];
     }
*/
   }
   
   $response = new ResourceResponse($response_array);
   $response->addCacheableDependency($response_array);
   return $response;

  }

  /**
   * Generates a fallback access denied message, when no specific reason is set.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $operation
   *   The disallowed entity operation.
   *
   * @return string
   *   The proper message to display in the AccessDeniedHttpException.
   */
  protected function generateFallbackAccessDeniedMessage(EntityInterface $entity, $operation) {
    $message = "You are not authorized to {$operation} this {$entity->getEntityTypeId()} entity";

    if ($entity->bundle() !== $entity->getEntityTypeId()) {
      $message .= " of bundle {$entity->bundle()}";
    }
    return "{$message}.";
  }

}
