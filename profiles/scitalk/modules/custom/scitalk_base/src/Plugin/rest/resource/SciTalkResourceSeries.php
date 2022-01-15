<?php
namespace Drupal\scitalk_base\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
//use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityStorageInterface;
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

use Drupal\taxonomy\Entity\Term;

//use Drupal\scitalk_base\ScitalkServices\ReferenceIDGenerator;
use Drupal\scitalk_base\ScitalkServices\ReferenceIDGeneratorInterface;
//use Drupal\pirsa_base\PirsaServices\ReferenceIDGenerator;

use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\rest\Plugin\rest\resource\EntityResourceAccessTrait;  

/**
  * Provides a Resource to get and patch SciTalk series
  *
  * @RestResource(
  *   id = "scitalk_talk_series",
  *   label = @Translation("SciTalk Talk REST API - Series"),
  *   entity_type = "taxonomy_term",
  *   serialization_class = "Drupal\taxonomy\Entity\Term",
  *   uri_paths = {
  *     "canonical" = "/api/talk/series/{series_id}",
  *     "https://www.drupal.org/link-relations/create" = "/api/talk/series"
  *   }
  * )
  */

class SciTalkResourceSeries extends ResourceBase {
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
 
  protected const VOCABULARY_NAME = 'series';
  protected $idGenerator;

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
  //public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, PluginManagerInterface $link_relation_type_manager, QueryFactory $entity_query, AccountProxyInterface $current_user, ReferenceIDGeneratorInterface $id_generator) {
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, PluginManagerInterface $link_relation_type_manager, EntityStorageInterface $entity_query, AccountProxyInterface $current_user, ReferenceIDGeneratorInterface $id_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->linkRelationTypeManager = $link_relation_type_manager;
    $this->currentUser = $current_user;
    $this->idGenerator = $id_generator;
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
      //$container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('scitalk_base.reference_id_generator')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param $series_id
   *   The pirsa number of the talk to retrieve
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($series_id = NULL) {
    if (empty($series_id)) {
      throw new BadRequestHttpException('No value for Series number received.');
    }

    if ($series_id == 'all') return $this->getAll();
    
    $taxonomy_query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', SciTalkResourceSeries::VOCABULARY_NAME)
      ->condition('name', $series_id, '=')
      ->range(0,1)
      ->execute();

    if ($taxonomy_query) {
     //load() needs an nid but taxonomy_query returns an array('vid' => 'nid') so get the nid first:
      $tid = current($taxonomy_query);
      $entity = Term::load($tid);

      $entity_access = $entity->access('view', NULL, TRUE);
      if (!$entity_access->isAllowed()) {
        //throw new CacheableAccessDeniedHttpException($entity_access, $entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'view'));
        throw new AccessDeniedHttpException( $this->generateFallbackAccessDeniedMessage($entity, 'view'));
      }

      $response = new ResourceResponse($entity, 200);
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
          //$this->logger->notice('Field name %f.', ['%f' => $field_name]);
        }
      }

    //$this->addLinkHeaders($entity, $response);   //should i add links?

      return $response;
    }
    else {
      return new ResourceResponse('Series not found: ' . $series_id, 200);
   //   throw new BadRequestHttpException('Invalid Series Number received: ' . $series_id);
    }
   
  }

  //fetch all series
  private function getAll() {
    $response_array = [];
    $taxonomy_query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', SciTalkResourceSeries::VOCABULARY_NAME)
      //->range(0,1)
      ->execute();

    if ($taxonomy_query) {
     //load() needs an nid but taxonomy_query returns an array('vid' => 'nid') so get the nid first:
     
      $entities = Term::loadMultiple($taxonomy_query);

      $no_talk_number_set = 0;
      foreach ($entities as $entity)
      {
        $entity_access = $entity->access('view', NULL, TRUE);
        if (!$entity_access->isAllowed()) {
          //throw new CacheableAccessDeniedHttpException($entity_access, $entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'view'));
          throw new AccessDeniedHttpException( $this->generateFallbackAccessDeniedMessage($entity, 'view'));
        }      

        if ($entity instanceof FieldableEntityInterface) {
          foreach ($entity as $field_name => $field) {
            /** @var \Drupal\Core\Field\FieldItemListInterface $field */
            $field_access = $field->access('view', NULL, TRUE);
            //$response->addCacheableDependency($field_access);

            if (!$field_access->isAllowed()) {
              $entity->set($field_name, NULL);
            }
            //$this->logger->notice('Field name %f.', ['%f' => $field_name]);
          }
        }

        $talk_number = $entity->name->value ?? $no_talk_number_set++;
        $response_array[$talk_number] = $entity;
      }
      
      $response = new ResourceResponse($response_array, 200);
      $response->addCacheableDependency($response_array);
     
      return $response;
    }
    else {
      return new ResourceResponse('No Series found.', 200);
      //throw new BadRequestHttpException('No series found');
    }
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $data 
   *   The object containing the talk data to save
   *
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function post($data) {
    if ($data == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    if ($data->bundle() != SciTalkResourceSeries::VOCABULARY_NAME) {
      throw new BadRequestHttpException('Invalid Type.');
    }

   \Drupal::logger('scitalk_base')->notice('<pre><code>data for Series START ' . print_r($data->toArray(), TRUE) . '</code></pre>');
    //create a series ID:
    $type = 'series';

    //call service to generate pirsa id:
    //$series_id = $this->idGenerator->generateReferenceId($type);
    $series_id = $this->idGenerator->generateReferenceId($data);

    //add it to the data:
    $data->set('name', $data->name->value);
    $data->set('description', $data->description->value);
    $data->set('field_collection_number', $series_id);
    $data->set('field_series_meeting_time', $data->field_series_meeting_time->value ?? '');
    $data->set('field_series_short_description', $data->field_series_short_description->value ?? '');
    $data->set('field_series_active', ($data->field_series_active->value || false));
    $data->set('path', '/'.$series_id);

    $isit = ($data instanceof \Drupal\taxonomy\Entity\Term) ? 'YES its a Taxonomy Term instance' : 'Not a Taxonomy term instance';
    $isit2 = ($data instanceof \Drupal\Core\Entity\EntityInterface) ? 'YES its a Entity instance' : 'Not an Entity instance';
    $isit3 = ($data instanceof \Drupal\node\Entity\Node) ? 'YES its a Node instance' : 'Not a node instance';

    $this->logger->notice('HELLO from bundle %data and entity type %type typeof %typeof', [
      '%data' => $data->bundle(), 
      '%type' => $data->getEntityTypeId(), 
      '%typeof'=>$isit3
      ]);
   \Drupal::logger('scitalk_base')->notice('<pre><code>data for Series isis ' . print_r($data->toArray(), TRUE) . '</code></pre>');

    $entity = $data;
    $entity_access = $entity->access('create', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new AccessDeniedHttpException($entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'create'));
    }
    $definition = $this->getPluginDefinition();
    // Verify that the deserialized entity is of the type that we expect to
    // prevent security issues.
    if ($entity->getEntityTypeId() != $definition['entity_type']) {
      throw new BadRequestHttpException('Invalid entity type');
    }
    // POSTed entities must not have an ID set, because we always want to create
    // new entities here.
    if (!$entity->isNew()) {
      throw new BadRequestHttpException('Only new entities can be created');
    }

    $this->checkEditFieldAccess($entity);

    // Validate the received data before saving.
    $this->validate($entity);
    try {
      $entity->save();
      $this->logger->notice('Created Series Talk %type with ID %id and Series Id %series_id.', ['%type' => $entity->getEntityTypeId(), '%id' => $entity->id(), '%series_id' => $entity->name->value]);

      // 201 Created responses return the newly created entity in the response
      // body. These responses are not cacheable, so we add no cacheability
      // metadata here.
      $headers = [];
      if (in_array('canonical', $entity->uriRelationships(), TRUE)) {
        $url = $entity->urlInfo('canonical', ['absolute' => TRUE])->toString(TRUE);
        $headers['Location'] = $url->getGeneratedUrl();
      }
      return new ModifiedResourceResponse($entity, 201, $headers);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }

  }

  /**
   * Responds to PATCH requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $series_id
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
  public function patch($series_id, $data, $request) {
    if (empty($series_id)) {
      throw new BadRequestHttpException('No value for series number received.');
    }

    if ($data == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    $definition = $this->getPluginDefinition();
    if ($data->getEntityTypeId() != $definition['entity_type']) {
      throw new BadRequestHttpException('Invalid entity type');
    }

   $taxonomy_query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', SciTalkResourceSeries::VOCABULARY_NAME)
      ->condition('name', $series_id, '=')
      ->range(0,1)
      ->execute();

   if ($taxonomy_query) {
     //load() needs an nid but taxonomy_query returns an array('vid' => 'nid') so get the nid first:
     $tid = current($taxonomy_query);
     $original_entity = Term::load($tid);

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
        $this->logger->notice('Updated entity %type with ID %id and Series number %series_id.', ['%type' => $original_entity->getEntityTypeId(), '%id' => $original_entity->id(), '%series_id' => $original_entity->name->value]);

        // Return the updated entity in the response body.
        return new ModifiedResourceResponse($original_entity, 200);
      }
      catch (EntityStorageException $e) {
        throw new HttpException(500, 'Internal Server Error', $e);
      }

   }
   else {
      throw new BadRequestHttpException('Invalid Series number received: ' . $series_id);
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
