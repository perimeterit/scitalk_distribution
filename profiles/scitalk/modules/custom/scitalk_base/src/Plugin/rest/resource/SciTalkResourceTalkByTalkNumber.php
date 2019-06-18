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

//use Drupal\scitalk_base\ScitalkServices\ReferenceIDGenerator;
use Drupal\scitalk_base\ScitalkServices\ReferenceIDGeneratorInterface;
//use Drupal\pirsa_base\PirsaServices\ReferenceIDGenerator;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\rest\Plugin\rest\resource\EntityResourceAccessTrait;  

/**
  * Provides a Resource to get and patch SciTalks
  *
  * @RestResource(
  *   id = "scitalk_talk_talk_number",
  *   label = @Translation("SciTalk Talk REST API - Talks by Talk number/ID"),
  *   entity_type = "node",
  *   serialization_class = "Drupal\node\Entity\Node",
  *   uri_paths = {
  *     "canonical" = "/api/talk/talks/{talk_number}",
  *     "https://www.drupal.org/link-relations/create" = "/api/talk/talks"
  *   }
  * )
  */

class SciTalkResourceTalkByTalkNumber extends ResourceBase {
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
 
  protected const NODE_TYPE = 'talk';
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, PluginManagerInterface $link_relation_type_manager, QueryFactory $entity_query, AccountProxyInterface $current_user, ReferenceIDGeneratorInterface $id_generator) {
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
      $container->get('entity.query'),
      $container->get('current_user'),
      $container->get('scitalk_base.reference_id_generator')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param $talk_number
   *   The talk number of the talk to retrieve
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($talk_number = NULL) {
    if (empty($talk_number)) {
      throw new BadRequestHttpException('No value for Talk number received.');
    }

    //get the node for this talk number
    $node_query = \Drupal::entityQuery('node')
     ->condition('status', 1)
     ->condition('type', SciTalkResourceTalkByTalkNumber::NODE_TYPE)
     ->condition('field_talk_number', $talk_number, '=')  //we're assuming no duplicates here or we're assuming the 1st one is the only one if there's a hit
     ->accessCheck(FALSE)
     ->execute();

    if ($node_query) {
     //load() needs an nid but node_query returns an array('vid' => 'nid') so get the nid first:
      $nid = current($node_query);
      $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $entity_access = $entity->access('view', NULL, TRUE);
      if (!$entity_access->isAllowed()) {
        //throw new CacheableAccessDeniedHttpException($entity_access, $entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'view'));
        throw new AccessDeniedHttpException($this->generateFallbackAccessDeniedMessage($entity, 'view'));
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

/*    // Randy commented code:
      //come back here and determine what to do here:

          //$field_access = $field->access('view', NULL, TRUE);
          //$response->addCacheableDependency($field_access);
//                     if (!$field_access->isAllowed()) {
//                       $entity->set($field_name, NULL);
//                     }
//                     else{
//                       if($field_name == 'field_talk_number') { //specific use case.  Decorate output here?
//                         $field->label='test';
//                         //$entity->set($field_name, $field);
                        
//                       }
//                     }

*/

      //$this->logger->notice('Field name %f.', ['%f' => $field_name]);
        }
      }

    //  $this->addLinkHeaders($entity, $response);   //should i add links?

      return $response;
    }
    else {
      throw new BadRequestHttpException('Invalid Talk number received: ' . $talk_number);
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

    //create a talk number here and add it to the data
    $type = 'video';
    $year =  date('y');
    $month = date('m');

    $year = substr($year, -2); //just chops it off at 2 to be sure
    $month = substr($month, -2);

    //call service to generate talk id:
    //$talk_num = $this->idGenerator->generateReferenceId($type, $year, $month);
    $talk_num = $this->idGenerator->generateReferenceId($data, $year, $month);

    //add it to the data:
    $data->set('field_talk_number', $talk_num);

 //   \Drupal::logger('scitalk_base')->notice('<pre><code>data is ' . print_r($data->toArray(), TRUE) . '</code></pre>');

    if (empty($data->field_talk_number->value)) {
     // throw new BadRequestHttpException('No value for Talk number received.');
    }
    
    $isit = $data instanceof \Drupal\Core\Entity\EntityInterface;

    $entity = $data;
    $this->logger->notice('HELLO there bundle: %data and entity %type typeof: %typeof', ['%data' => $data->bundle(), '%type' => $data->getEntityTypeId(), '%typeof'=>$isit]);
    \Drupal::logger('scitalk_base')->notice('<pre><code>data for Series isis ' . print_r($data->toArray(), TRUE) . '</code></pre>');

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
      $this->logger->notice('Created Talk %type with ID %id and Talk number %talk.', ['%type' => $entity->getEntityTypeId(), '%id' => $entity->id(), '%talk' => $entity->field_talk_number->value]);

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


/*
    $permission = 'Access POST on SciTalk Talk REST API get by PIRSA number resource';
    //$can_post = $this->currentUser->hasPermission($permission);
    //$response_array['has permission'] = $can_post ? 'yes' : 'no';
    if(!$this->currentUser->hasPermission($permission)) {
        throw new AccessDeniedHttpException();
    }
*/
 //   \Drupal::logger('scitalk_base')->warning('<pre><code>POST' . print_r($node_type, TRUE) . '</code></pre>');
   // \Drupal::logger('scitalk_base')->warning('<pre><code>POST data' . print_r($data, TRUE) . '</code></pre>');

//$node['data']=$data;
//$acc = $data->access('create', NULL, TRUE);
//$node['access_alowed']=$acc->isAllowed() ? ' yes ' : ' nope ';

/*
    $node =// Node::create(
      array(
        'type' => 'talk',//$node_type,
        'title' => $data->title->value,
        'body' => [
          'summary' => '',
          'value' => $data->body->value,
          'format' => 'full_html',
        ],
     // )
    );
*/
   // $node->save();
//    return new ResourceResponse($node);

//    $url = $node->urlInfo('canonical', ['absolute' => TRUE])->toString(TRUE);
//    return new ModifiedResourceResponse($node, 201, ['Location' => $url->getGeneratedUrl()]);
  }

  /**
   * Responds to PATCH requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $talk_number
   *   the talk number of the talk to save
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
  public function patch($talk_number, $data, $request) {
    if (empty($talk_number)) {
      throw new BadRequestHttpException('No value for Talk number received.');
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
     ->condition('type', SciTalkResourceTalkByTalkNumber::NODE_TYPE)
     ->condition('field_talk_number', $talk_number, '=')
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
        $this->logger->notice('Updated entity %type with ID %id and Talk number %talk.', ['%type' => $original_entity->getEntityTypeId(), '%id' => $original_entity->id(), '%talk' => $original_entity->field_talk_number->value]);

        // Return the updated entity in the response body.
        return new ModifiedResourceResponse($original_entity, 200);
      }
      catch (EntityStorageException $e) {
        throw new HttpException(500, 'Internal Server Error', $e);
      }

   }
   else {
      throw new BadRequestHttpException('Invalid Talk number received: ' . $talk_number);
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
