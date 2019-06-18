<?php
namespace Drupal\scitalk_base\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;


use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\rest\Plugin\rest\resource\EntityResourceAccessTrait;  

/**
  * Provides a Resource to get and patch SciTalks
  *
  * @RestResource(
  *   id = "scitalk_talk_nid",
  *   label = @Translation("SciTalk Talk REST API - Talks by nid"),
  *   entity_type = "node",
  *   serialization_class = "Drupal\node\Entity\Node",
  *   uri_paths = {
  *     "canonical" = "/api/talk/nid/{nid}",
  *   }
  * )
  */

class SciTalkResourceTalkByNID extends ResourceBase {
  use EntityResourceValidationTrait;
  use EntityResourceAccessTrait;  

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

   /**
   * The link relation type manager used to create HTTP header links.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $linkRelationTypeManager;

  private const NODE_TYPE = 'talk';

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $link_relation_type_manager
   *   The link relation type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, PluginManagerInterface $link_relation_type_manager, QueryFactory $entity_query, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->linkRelationTypeManager = $link_relation_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory'),
      $container->get('plugin.manager.link_relation_type'),
      $container->get('entity.query'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param $nid
   *   The pirsa number of the talk to retrieve
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($nid = NULL) {
    if (empty($nid)) {
      throw new BadRequestHttpException('No value for Pirsa number received.');
    }

    //get the node for this pirsa number
    $node_query = \Drupal::entityQuery('node')
     ->condition('status', 1)
     ->condition('type', SciTalkResourceTalkByNID::NODE_TYPE)
     ->condition('nid', $nid, '=')
     ->accessCheck(FALSE)
     ->execute();

    if ($node_query) {
     //load() needs an nid but node_query returns an array('vid' => 'nid') so get the nid first:
      $nid = current($node_query);
      $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $entity_access = $entity->access('view', NULL, TRUE);
      if (!$entity_access->isAllowed()) {
        //throw new CacheableAccessDeniedHttpException($entity_access, $entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'view'));
        throw new AccessDeniedHttpException( $this->generateFallbackAccessDeniedMessage($entity, 'view'));
      }

      $response = new ResourceResponse( $entity, 200);
      $response->addCacheableDependency($entity);
      $response->addCacheableDependency($entity_access);

      if ($entity instanceof FieldableEntityInterface) {
        foreach ($entity as $field_name => $field) {
          /** @var \Drupal\Core\Field\FieldItemListInterface $field */
          $field_access = $field->access('view', NULL, TRUE);
          $response->addCacheableDependency($field_access);

          if (!$field_access->isAllowed()) {
            $entity->set($field_name, NULL);
          }
        }
      }

    //  $this->addLinkHeaders($entity, $response);

      return $response;
    }
    else {
      throw new BadRequestHttpException('Invalid nid received: ' . $nid);
    }
   
  }


  /**
   * Responds to PATCH requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $pirsa_number
   *   the pirsa number of the talk to save
   *
   * @param $data
   *   The object containing the talk data to save
   *
   * @param $request
   *   contains the request object
   *
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function patch($nid, $data, $request) {
    if (empty($nid)) {
      throw new BadRequestHttpException('No value for nid received.');
    }

    if ($data == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    $definition = $this->getPluginDefinition();
    if ($data->getEntityTypeId() != $definition['entity_type']) {
      throw new BadRequestHttpException('Invalid entity type');
    }

    //load the talk entity that we are going to update (by PIRSA number):
    $node_query = \Drupal::entityQuery('node')
     ->condition('status', 1)
     ->condition('type', SciTalkResourceTalkByNID::NODE_TYPE)
     ->condition('nid', $nid, '=')
     ->accessCheck(FALSE)
     ->execute();

   if ($node_query) {
     //load() needs an nid but node_query returns an array('vid' => 'nid') so get the nid first:
     $nid = current($node_query);
     $original_entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

     $entity_access = $original_entity->access('update', NULL, TRUE);
     if (!$entity_access->isAllowed()) {
       throw new AccessDeniedHttpException($entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($data, 'update'));
     }

     //fields sent in the PATCH request:
     $reqObj = json_decode($request->getContent());
     foreach ($reqObj as $field_name => $v) {
       $field = $data->get($field_name);
       if ($data->getEntityType()->hasKey('langcode') && $field_name === $data->getEntityType()->getKey('langcode') && $field->isEmpty()) {
         continue;
       }

       if ($this->checkPatchFieldAccess($original_entity->get($field_name), $field)) {
         $original_entity->set($field_name, $field->getValue());
       }

       $update[$field_name] = $data->get($field_name)->getValue();
     }

      // Validate the received data before saving.
      $this->validate($original_entity);
      try {
        $original_entity->save();
        $this->logger->notice('Updated entity %type with ID %id and PIRSA number %pirsa.', ['%type' => $original_entity->getEntityTypeId(), '%id' => $original_entity->id(), '%pirsa' => $original_entity->field_pirsa_number->value]);

        // Return the updated entity in the response body.
        return new ModifiedResourceResponse($original_entity, 200);
      }
      catch (EntityStorageException $e) {
        throw new HttpException(500, 'Internal Server Error', $e);
      }

   }
   else {
      throw new BadRequestHttpException('Invalid node ID received: ' . $nid);
   }

  }

 /**
   * Checks whether the given field should be PATCHed.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $original_field
   *   The original (stored) value for the field.
   * @param \Drupal\Core\Field\FieldItemListInterface $received_field
   *   The received value for the field.
   *
   * @return bool
   *   Whether the field should be PATCHed or not.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user sending the request is not allowed to update the
   *   field. Only thrown when the user could not abuse this information to
   *   determine the stored value.
   *
   * @internal
   */
  protected function checkPatchFieldAccess(FieldItemListInterface $original_field, FieldItemListInterface $received_field) {
    // If the user is allowed to edit the field, it is always safe to set the
    // received value. We may be setting an unchanged value, but that is ok.
    $field_edit_access = $original_field->access('edit', NULL, TRUE);
    if ($field_edit_access->isAllowed()) {
      return TRUE;
    }

    // The user might not have access to edit the field, but still needs to
    // submit the current field value as part of the PATCH request. For
    // example, the entity keys required by denormalizers. Therefore, if the
    // received value equals the stored value, return FALSE without throwing an
    // exception. But only for fields that the user has access to view, because
    // the user has no legitimate way of knowing the current value of fields
    // that they are not allowed to view, and we must not make the presence or
    // absence of a 403 response a way to find that out.
    if ($original_field->access('view') && $original_field->equals($received_field)) {
      return FALSE;
    }

    // It's helpful and safe to let the user know when they are not allowed to
    // update a field.
    $field_name = $received_field->getName();
    $error_message = "Access denied on updating field '$field_name'.";
    if ($field_edit_access instanceof AccessResultReasonInterface) {
      $reason = $field_edit_access->getReason();
      if ($reason) {
        $error_message .= ' ' . $reason;
      }
    }
    throw new AccessDeniedHttpException($error_message);
  }

  /**
   * Adds link headers to a response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   *
   * @see https://tools.ietf.org/html/rfc5988#section-5
   */
  protected function addLinkHeaders(EntityInterface $entity, Response $response) {
    foreach ($entity->uriRelationships() as $relation_name) {
      if ($this->linkRelationTypeManager->hasDefinition($relation_name)) {
        /** @var \Drupal\Core\Http\LinkRelationTypeInterface $link_relation_type */
        $link_relation_type = $this->linkRelationTypeManager->createInstance($relation_name);

        $generator_url = $entity->toUrl($relation_name)
          ->setAbsolute(TRUE)
          ->toString(TRUE);
        if ($response instanceof CacheableResponseInterface) {
          $response->addCacheableDependency($generator_url);
        }
        $uri = $generator_url->getGeneratedUrl();

        $relationship = $link_relation_type->isRegistered()
          ? $link_relation_type->getRegisteredName()
          : $link_relation_type->getExtensionUri();

        $link_header = '<' . $uri . '>; rel="' . $relationship . '"';
        $response->headers->set('Link', $link_header, FALSE);
      }
    }
  }

}
