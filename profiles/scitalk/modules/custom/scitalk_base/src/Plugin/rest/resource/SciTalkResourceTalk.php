<?php
namespace Drupal\scitalk_base\Plugin\rest\resource;
/*
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\ResourceInterface;
use Drupal\rest\ResourceResponse;
*/
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Psr\Log\LoggerInterface;

/**
  * Provides a Resource to get and patch SciTalks
  *
  * @RestResource(
  *   id = "scitalk_talk_by_entity",
  *   label = @Translation("SciTalk Talk REST API - Talks by entity"),
  *   entity_type = "node",
  *   serialization_class = "Drupal\node\Entity\Node",
  *   uri_paths = {
  *     "canonical" = "/api/talk/{node}",
  *     "https://www.drupal.org/link-relations/create" = "/api/talk"
  *   }
  * )
  */

//class SciTalkResourceTalk extends ResourceBase {
class SciTalkResourceTalk extends EntityResource {
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

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   */
  public function post(EntityInterface $entity = NULL) {

$this->logger->notice('hello from post start');

    $response = parent::post($entity);
    return $response;
//$log='<pre>'.print_r($entity,true).'</pre>';
//\Drupal::logger('scitalk_base')->notice($log);
    //return new ResourceResponse($entity);
   
    $url = $entity->urlInfo('canonical', ['absolute' => TRUE])->toString(TRUE);
    return new ModifiedResourceResponse($entity, 201, ['Location' => $url->getGeneratedUrl()]);
  }
 
}
