<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\Group;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;
use Drupal\scitalk_base\SciVideosIntegration\Entities\Talk;
use Drupal\scitalk_base\SciVideosIntegration\Entities\SpeakerProfile;
use Drupal\scitalk_base\SciVideosIntegration\Entities\Collection;
use Drupal\scitalk_base\SciVideosIntegration\Entities\Vocabularies;
use Drupal\scitalk_base\SciVideosIntegration\Entities\VocabularyTerms;
use Drupal\scitalk_base\SciVideosIntegration\Entities\TalkKeyword;
use Exception;

class SciVideosIntegration {
  private $configFactory;
  private $entityTypeManager;
  private $tempStoreFactory;
  private $messenger;
  private $dateFormatter;

  private $scivideos;
  private $speakerProfile;
  private $talk;
  private $collection;
  private $talkKeyword;

  public function __construct(EntityTypeManager $entity_type_manager, ConfigFactoryInterface $config_factory, PrivateTempStoreFactory $temp_store, MessengerInterface $messenger, DateFormatter $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->tempStoreFactory = $temp_store;
    $this->messenger = $messenger;
    $this->dateFormatter = $date_formatter;

    $this->scivideos = SciVideosAuthentication::getInstance($this->tempStoreFactory);
    $this->talk = new Talk($this->scivideos);
    $this->speakerProfile = new SpeakerProfile($this->scivideos);
    $this->collection = new Collection($this->scivideos);
    $this->talkKeyword = new TalkKeyword($this->scivideos);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('tempstore.private'),
      $container->get('messenger'),
      $container->get('date.formatter')
    );
  }

  /**
   * fetch list of Vocabularies from SciVideos
   */
  public function fetchVocabularies() {
    $vobularies = new Vocabularies($this->scivideos);
    return $vobularies->fetch();
  }

  /**
   * fetch list of terms under a Vocabulary from SciVideos
   * 
   * @param mixed $vocabulary_name
   */
  public function fetchVocabularyTerms($vocabulary_name) {
    $vobularies = new VocabularyTerms($this->scivideos, $vocabulary_name);
    return $vobularies->fetch();
  }

  /**
   * add SciVideos Talk
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addTalk(EntityInterface $entity) {
    $talk = $this->buildTalk($entity);

    try {
      $response = $this->talk->create($talk);
      $scivideo_talk = json_decode($response);

      // set integration id
      if (!empty($entity->field_scivideos_uuid)) {
        unset($entity->field_scivideos_uuid);
      }

      $entity->set('field_scivideos_uuid', $scivideo_talk->data->id);
      $entity->save();

      return $scivideo_talk;

    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * Update SciVideos Talk
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function updateTalk(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    $talk = $this->buildTalk($entity);

    try {
      $response = $this->talk->update($talk);
      $scivideo_talk = json_decode($response);

      return $scivideo_talk;
    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * Delete SciVideos Talk
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function deleteTalk(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    $talk = $this->buildTalk($entity);

    try {
      $response = $this->talk->delete($talk);
      $scivideo_talk = json_decode($response);
      return $scivideo_talk;
    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * add SciVideos Collection
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addCollection(EntityInterface $entity) {
    $collection = $this->buildCollection($entity);

    try {
      $response = $this->collection->create($collection);
      $scivideo_collection = json_decode($response);

      // set integration id
      if (!empty($entity->field_scivideos_uuid)) {
        unset($entity->field_scivideos_uuid);
      }

      $entity->set('field_scivideos_uuid', $scivideo_collection->data->id);
      $entity->save();

      return $scivideo_collection;

    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * UpdateSciVideos Collection
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function updateCollection(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    $collection = $this->buildCollection($entity);

    try {
      $response = $this->collection->update($collection);
      $scivideo_collection = json_decode($response);

      return $scivideo_collection;
    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * Delete SciVideos Collection
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function deleteCollection(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    // //already doing this from '_scitalk_base_delete_entity_validate' in scitalk_base.module
    // //double test it's possible to delete the collection (it should be prevented when trying to delete in hook_form_alter)
    // if ($number_of_talks = $this->getNumberOfTalksUnderCollection($entity)) {
    //   $this->messenger->addWarning("Please note that this Collection was not deleted from SciVideos as we found {$number_of_talks} Talk(s) under the Collection.");
    //   return;
    // }

    $collection = $this->buildCollection($entity);

    try {
      $response = $this->collection->delete($collection);
      $scivideo_collection = json_decode($response);
      return $scivideo_collection;
    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * add SciVideos Speaker Profile
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addSpeakerProfile(EntityInterface $entity) {
    $speaker = $this->buildSpeakerProfile($entity);

    try {
      $response = $this->speakerProfile->create($speaker);
      $scivideo_speaker = json_decode($response);

      // set integration id
      if (!empty($entity->field_scivideos_uuid)) {
        unset($entity->field_scivideos_uuid);
      }
      $entity->set('field_scivideos_uuid', $scivideo_speaker->data->id);
      $entity->save();

      return $scivideo_speaker;
    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * Update SciVideos Speaker Profile
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function updateSpeakerProfile(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    $speaker = $this->buildSpeakerProfile($entity);
    try {
      $response = $this->speakerProfile->update($speaker);
      $scivideo_speaker = json_decode($response);
      return $scivideo_speaker;
    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * Delete SciVideos Speaker Profile
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function deleteSpeakerProfile(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    // //doing this already in "_scitalk_base_delete_entity_validate" from the scitalk_base.module:
    // //double test it's possible to delete the speaker profile (it should be prevented when trying to delete in hook_form_alter)
    // if ($number_of_talks = $this->getNumberOfTalksForSpeakerInSciVideos($entity)) {
    //   $name = $entity->field_sp_display_name->value ?? $entity->field_sp_first_name->value;
    //   $this->messenger->addWarning("Please note that this Speaker Profile was not deleted from SciVideos as we found {$number_of_talks} Talk(s) by {$name}.");
    //   return;
    // }

    $speaker_profile = $this->buildSpeakerProfile($entity);

    try {
      $response = $this->speakerProfile->delete($speaker_profile);
      $scivideo_speaker_profile = json_decode($response);
      return $scivideo_speaker_profile;
    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

   /**
   * get all stats under a Collection including number of talks and number of subcollections from SciVideos
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function getCollectionChildrenStats(EntityInterface $entity): array {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return 0;
    }

    $collection_query = $this->collection->fetchById($scivideos_uuid);
    $collection = json_decode($collection_query);
    $collection_data = $collection->data->attributes;
    $number_of_talks = $collection_data->collection_number_of_talks ?? 0;
    $number_of_subcollections = $collection_data->collection_number_children ?? 0;
    $title = $collection_data->title ?? 'This Collection';
    return ['number_of_talks' => $number_of_talks, 'number_of_subcollections' => $number_of_subcollections, 'title' => $title];
  }

  /**
   * get Number of Talks under a Collection in SciVideos
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function getNumberOfTalksUnderCollection(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return 0;
    }

    $talks_under_collection_query = $this->collection->fetchById($scivideos_uuid);
    $talks_under_collection = json_decode($talks_under_collection_query);
    $number_of_talks = $talks_under_collection->data->attributes->collection_number_of_talks ?? 0;
    return $number_of_talks;

    // $talks_under_collection_query = $this->talk->fetchTalksUnderCollectionById($scivideos_uuid);
    // $talks_under_collection = json_decode($talks_under_collection_query);
    // $number_of_talks = $talks_under_collection->meta->count ?? count($talks_under_collection->data);
    // return $number_of_talks;
  }

  /**
   * get Number of Talks for a Speaker in SciVideos
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function getNumberOfTalksForSpeakerInSciVideos(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return 0;
    }
    $talks_for_speaker_query = $this->talk->fetchTalksBySpeakerProfileId($scivideos_uuid);
    $talks_for_speaker = json_decode($talks_for_speaker_query);
    $number_of_talks = $talks_for_speaker->meta->count ?? count($talks_for_speaker->data);
    return $number_of_talks;
  }

   /**
   * get Number of Talks for a Speaker in the local site
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
    public function getNumberOfTalksForSpeakerLocalSite(EntityInterface $entity) {
      $tid = $entity->nid->value ?? 0;
      $query_count = \Drupal::entityQuery('node')
          ->condition('type', 'talk')
          // ->condition('status', 1)
          ->condition('field_talk_speaker_profile.target_id', $tid);

      return $query_count->count()->execute() ?? 0;
    }

  /**
   * get Number of Talks for a Speaker in SciVideos
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function getNumberOfCollectonSubCollections(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return 0;
    }

    $collections_query = $this->collection->fetchById($scivideos_uuid);
    $collections = json_decode($collections_query);
    $number_of_collections = $collections->data->attributes->collection_number_children ?? 0;
    return $number_of_collections;

    // $subcollections_query = $this->collection->fetchCollectionChildrenById($scivideos_uuid);
    // $subcollections = json_decode($subcollections_query);
    // $number_of_subcollections = $subcollections->meta->count ?? count($subcollections->data);
    // return $number_of_subcollections;
  }

  /**
   * Link a local entity with its SciVideos corresponding entity
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   * @param string $entity_identifier
   * @param bool $update
   */
  public function linkToSciVideos(EntityInterface $entity, $entity_indentifier = '', $update = FALSE) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      $intentifier_value = $entity->{$entity_indentifier}->value ?? '';
      $method_call = $this->getCallable($entity_indentifier);
      $entity_search = $method_call($intentifier_value);
      $entity_search = json_decode($entity_search);
      $found = $entity_search->meta->count ?? count($entity_search->data);
      if ($found > 0) {
        $uuid = current($entity_search->data)->id;
        $entity->set('field_scivideos_uuid', $uuid);
        if ($update) {
          $entity->save();  //update this entity scivideo uuid
        }
      }
    }
    return $entity_search;
  }

 /**
   * return a callable function to update a SciVideo entity depending on what field is being used
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   * @param string $entity_identifier
   * @return callable
   */
  private function getCallable($field_indentifier): callable {
    $method = [];
    switch ($field_indentifier) {
      case 'field_talk_number':
        $method = [$this->talk, 'fetchByTalkNumber'];
        break;
      case 'field_collection_number':
        $method = [$this->collection, 'fetchByCollectionNumber'];
        break;
      case 'field_sp_external_id':
        $method = [$this->speakerProfile, 'fetchByExternalID'];
        break;
    }
    return $method;
  }

  /**
   * create Talk object
   * 
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  private function buildTalk(EntityInterface $entity) {
    $talk = [
      "data" => [
          "type" => "talk",
          "attributes" => [
              "title" => $entity->title->value ?? '',
              "field_talk_abstract" => [
                  "value" => $entity->field_talk_abstract->value ?? '',
                  "format" => "basic_html"
              ],
              "field_talk_location" => $entity->field_talk_location->value ?? '',
              "field_talk_viewable_online" => $entity->field_talk_viewable_online->value ?? FALSE,
              "field_talk_number" => $entity->field_talk_number->value,
              "field_talk_doi" => $entity->field_talk_doi->value ?? '',
              "field_talk_speakers_text" => $entity->field_talk_speakers_text->value ?? '',
              "status" => $entity->status->value,
          ],
          "relationships" => []
      ]
    ];

    $integration_id = $entity->field_scivideos_uuid->value ?? '';
    if (!empty($integration_id)) {
      $talk["data"]["id"] = $integration_id;
    }

    if (!empty($entity->field_talk_date->value)) {
      $talk["data"]["attributes"]["field_talk_date"] = $this->formatDate( $entity->field_talk_date->value );
    }
    else {
      $talk["data"]["attributes"]["field_talk_date"] = NULL;
    }

    $talk_url = $entity->toUrl()->setAbsolute()->toString(true)->getGeneratedUrl() ?? '';
    //on create, if there's no repository group set on the site then the above $talk_url points to links like this: 'https://site.com/node/123'
    //this code will catch those cases and set the url to point to the talk alias:
    if ((strpos($talk_url, 'node/') !== 0) && empty($entity->get('field_talk_source_repository')->target_id)  ) {
      $base_path = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
      $talk_url = $base_path . $entity->field_talk_number->value ?? '';
    }

    $talk["data"]["attributes"]["field_talk_video_url"] = [
      "uri" => $talk_url,
      "title" => $talk_url,
      "options" => ['attributes' => ['target' => '_blank'] ]
    ];


    if (!empty($entity->field_talk_source_event->getValue())) {
      $talk["data"]["attributes"]["field_talk_source_event"] = [
        "uri" => $this->getExternalUrl($entity->field_talk_source_event),
        "title" => $entity->field_talk_source_event->title,
        "options"=> ['attributes' => ['target' => '_blank'] ]
      ];
    }
    else {
      $talk["data"]["attributes"]["field_talk_source_event"] = [];
    }

    $speakers = $entity->get('field_talk_speaker_profile')->getValue() ?? [];
    if (!empty($speakers)) {
      $mapped_speakers = $this->mapSpeakers($speakers);
      $talk["data"]["relationships"]["field_talk_speaker_profile"] = $mapped_speakers;
    }
    else {  //make sure to force delete all speakers when none is set
      $talk["data"]["relationships"]["field_talk_speaker_profile"] = [ "data" => [] ];
    }

    
    $talk["data"]["relationships"]["field_talk_source_repository"] = $this->getSciVideosSourceRepository();
    $talk["data"]["relationships"]["field_talk_type"] = $this->mapTalkType($entity->get('field_talk_type'));
    $talk["data"]["relationships"]["field_talk_collection"] = $this->mapCollection($entity->get('field_talk_collection'));
    $talk["data"]["relationships"]["field_talk_subject"] = $this->mapSubjects($entity->get('field_talk_subject'));
    $talk["data"]["relationships"]["field_scientific_area"] = $this->mapScientificAreas($entity->get('field_scientific_area'));

    $keywords = $entity->get('field_talk_keywords')->getValue() ?? [];
    if (!empty($keywords)) {
      $talk["data"]["relationships"]["field_talk_keywords"] = $this->mapKeywords($keywords);
    }
    else {
      $talk["data"]["relationships"]["field_talk_keywords"] = [ "data" => [] ];

    }

    return $talk;
  }

  /**
   * create Collection object
   * 
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  private function buildCollection(EntityInterface $entity) {
    $collection = [
      "data" => [
        "type" => "collection",
        "attributes" => [
            "title" => $entity->title->value,
            "field_collection_number" => $entity->field_collection_number->value ?? '',
            "field_collection_description" => [
              "value" => stripslashes(stripslashes(($entity->field_collection_description->value ?? ''))) ?? '',
              "format" => "basic_html"
            ],
            "field_collection_short_desc" => [
              "value" => $entity->field_collection_short_desc->value ?? '',
              "format" => "basic_html"
            ],
            // "field_collection_date" => [
            //    "value" => $this->formatDate( $entity->field_collection_date->value ) ?? NULL,
            //    "end_value" => $this->formatDate( $entity->field_collection_date->end_value ) ?? NULL,
            // ],
            "field_collection_location" => $entity->field_collection_location->value ?? '',
            "field_collection_public_viewable" => $entity->field_collection_public_viewable->value,
            "status" => $entity->status->value,
          ],
        "relationships" => []
      ]
    ];

    $integration_id = $entity->field_scivideos_uuid->value ?? '';
    if (!empty($integration_id)) {
      $collection["data"]["id"] = $integration_id;
    }

    if (!empty($entity->field_collection_date->value)) {
      $collection["data"]["attributes"]["field_collection_date"]["value"] = $this->formatDate( $entity->field_collection_date->value ) ?? NULL;
      $collection["data"]["attributes"]["field_collection_date"]["end_value"] = $this->formatDate( $entity->field_collection_date->end_value ) ?? NULL;
    }
    else {
      $collection["data"]["attributes"]["field_collection_date"]["value"] = NULL;
      $collection["data"]["attributes"]["field_collection_date"]["end_value"] = NULL;
    }

    if (!empty($entity->field_collection_event_url->getValue())) {
      $collection["data"]["attributes"]["field_collection_event_url"] = [
        "uri" => $this->getExternalUrl($entity->field_collection_event_url),
        "title" => $entity->field_collection_event_url->title,
        "options"=> ['attributes' => ['target' => '_blank'] ]
      ];
    }
    else {
      $collection["data"]["attributes"]["field_collection_event_url"] = [];
    }

    $speakers = $entity->get('field_collection_organizers')->getValue() ?? [];
    if (!empty($speakers)) {
      $mapped_speakers = $this->mapSpeakers($speakers);
      $collection["data"]["relationships"]["field_collection_organizers"] = $mapped_speakers;
    }
    else {  //make sure to force delete all speakers when none is set
      $collection["data"]["relationships"]["field_collection_organizers"] = [ "data" => [] ];
    }

    $collection["data"]["relationships"]["field_collection_source_repo"] = $this->getSciVideosSourceRepository();
    $collection["data"]["relationships"]["field_collection_subject"] = $this->mapSubjects($entity->get('field_collection_subject'));
    $collection["data"]["relationships"]["field_collection_type"] = $this->mapCollectionType($entity->get('field_collection_type'));
    $collection["data"]["relationships"]["field_parent_collection"] = $this->mapCollection($entity->get('field_parent_collection'));

    return $collection;
  }

  /**
   * create SpeakerProfile SciVideo object
   * 
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  private function buildSpeakerProfile(EntityInterface $entity) {
    $speaker_profile = [
      "data" => [
            "type" => "speaker_profile",
            "attributes" => [
                "title" => $entity->title->value,
                "display_name" => $entity->field_sp_display_name->value ?? '',
                "external_id" => $entity->field_sp_external_id->value ?? '',
                "username" => $entity->field_sp_username->value ?? '',
                "first_name" => $entity->field_sp_first_name->value ?? '',
                "last_name" => $entity->field_sp_last_name->value ?? '',
                "institution_name" => $entity->field_sp_institution_name->value ?? '',
                "field_sp_orcid_id" => $entity->field_sp_orcid_id->value ?? '',
                "status" => $entity->status->value,
                "speaker_profile" => [
                    "value" => $entity->field_sp_speaker_profile->value ?? '',
                    "format" => "basic_html",
                ],
                "web_profile_url" => [
                    "uri" => $entity->field_sp_web_profile_url->uri ?? '',
                    "title" => $entity->field_sp_web_profile_url->title ?? '',
                    "options" => ['attributes' => ['target' => '_blank'] ]
                ]
            ]
      ]
    ];

    $integration_id = $entity->field_scivideos_uuid->value ?? '';
    if (!empty($integration_id)) {
      $speaker_profile["data"]["id"] = $integration_id;
    }

    return $speaker_profile;
  }

  /**
   * map local speakers to their SciVideos records
   * 
   * @param mixed $speakers
   */
  private function mapSpeakers($speakers) {
    $speaker_ids = array_map(function($speaker) {
      if (empty($speaker)) {
        return [];
      }

      $target_id = $speaker['target_id'];
      $speaker_entity = $this->entityTypeManager->getStorage('node')->load($target_id);

      $uuid = $speaker_entity->field_scivideos_uuid->value ?? '';
      if (!empty($uuid)) {
        return $uuid;
      }

      //try and fetch using the external id
      $external_id = $speaker_entity->field_sp_external_id->value ?? '';
      if (!empty($external_id)) {
        $speaker_search = $this->speakerProfile->fetchByExternalID($external_id);

        $speaker_search = json_decode($speaker_search);
        $found = $speaker_search->meta->count ?? count($speaker_search->data);
        if ($found > 0) {
          $uuid = current($speaker_search->data)->id;
          $speaker_entity->set('field_scivideos_uuid', $uuid)->save();  //update this speaker scivideo uuid
          return $uuid;
        }
      }

      //otherwise, create speaker
      $new_speaker = $this->addSpeakerProfile($speaker_entity);
      return $new_speaker->data->id ?? '';

    }, $speakers);

    //remove any empty or not mapped speaker
    $speaker_ids = array_filter($speaker_ids, function($speaker) {
        return !empty($speaker);
    });

    $speaker_ids = array_values($speaker_ids);

    if (empty(current($speaker_ids))) {
      return [ "data" => [] ];
    }

    $speakers_map = array_map(function($itm) {
        return ["type" => "speaker_profile",
                "id" => $itm
        ];
    }, $speaker_ids);

    return [ "data" => $speakers_map ];
  }
  
  /**
   * date formating
   * 
   * @param string $utc_date
   * @param string $format
   * @param string $timezone
   */
  private function formatDate($utc_date = '', $format = 'Y-m-d\TH:i:sP', $timezone = '') {
    if (empty($utc_date)) {
      return NULL;
    }

    $drupal_date = new DrupalDateTime($utc_date, 'UTC');
    $timestamp = strtotime($drupal_date);
    if (empty($timezone)) {
      $timezone = date_default_timezone_get();
    }
    return $this->dateFormatter->format($timestamp, 'custom', $format, $timezone);
  }

  /**
   * create Source Repo mapping object
   */
  private function getSciVideosSourceRepository() {
    $config = $this->configFactory->get('scitalk_base.settings');
    $scivideos_group_uuid = $config->get('scivideos_group_uuid') ?? '';

    return [ 
      "data" => [
        "type" => "group--source_repository",
        "id" => $scivideos_group_uuid
      ]
    ];
  }

  /**
   * map local talk type to SciVideos objects
   * 
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $talk_type
   */
  private function mapTalkType(EntityReferenceFieldItemListInterface $talk_type) {
    return $this->getMappings($talk_type, 'talk_type');
  }

  /**
   * map local collection types to SciVideos objects
   * 
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $collection_types
   */
  private function mapCollectionType(EntityReferenceFieldItemListInterface $collection_types) {
    return $this->getMappings($collection_types, 'collection_type');
  }

  /**
   * map local subjects to SciVideos objects
   * 
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $subjects
   */
  private function mapSubjects(EntityReferenceFieldItemListInterface $subjects) {
    return $this->getMappings($subjects, 'subjects');
  }

  /**
   * map local scientific areas to SciVideos objects
   * 
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $sci_areas
   */
  private function mapScientificAreas(EntityReferenceFieldItemListInterface $sci_areas) {
    return $this->getMappings($sci_areas, 'scientific_area');
  }

  /**
   * create mapping objects for a specific field
   * 
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   * @param string $mapping_type
   */
  private function getMappings(EntityReferenceFieldItemListInterface $field, string $mapping_type) {
    $config_file = $this->getConfigMappingFile($field);
    $config = $this->configFactory->get($config_file);
    $values = $field->getValue();
    $mappings = [];
    $term_mappings = $config->get('term_mappings');
    foreach ($values as $val) {
      $mapping = $this->getSciVideosMappedTermId($term_mappings, $val);
      if (!empty($mapping)) {
        $mappings[] = [
          'type' => $mapping_type,
          'id' => $mapping
        ];
      }
    }

    return ['data' => $mappings];
  }

  /**
   * map local collections from the local site to collections in SciVideos
   * 
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $collections_list
   */
  private function mapCollection(EntityReferenceFieldItemListInterface $collections_list) {
    $collections = $collections_list->getValue();

    //filter out empty collection items:
    $collections =  array_filter($collections, function($itm) {
      return !empty($itm);
    });

    //check if the parent collection has a scivideo uuid
    $collections_ids = array_map(function($collection) {
      if (empty($collection)) {
        return [];
      }

      $target_id = $collection['target_id'];
      $collection_entity = $this->entityTypeManager->getStorage('node')->load($target_id);

      $uuid = $collection_entity->field_scivideos_uuid->value ?? '';
      if (!empty($uuid)) {
        return $uuid;
      }

      //if no scivideo uuid, then fetch it from SciVideos using the collection number, then update parent uuid
      $collection_number = $collection_entity->field_collection_number->value ?? '';
      if (!empty($collection_number)) {
        $collection_search = $this->collection->fetchByCollectionNumber($collection_number);

        $collection_search = json_decode($collection_search);
        $found = $collection_search->meta->count ?? count($collection_search->data);
        if ($found > 0) {
          $uuid = current($collection_search->data)->id;
          $collection_entity->set('field_scivideos_uuid', $uuid)->save();  //update this collection scivideo uuid
          return $uuid;
        }
      }

      return NULL;

    }, $collections);

    if (empty(current($collections_ids))) {
      return [ "data" => [] ];
    }

    $collections_map = array_map(function($itm) {
        return ["type" => "collection",
                "id" => $itm
        ];
    }, $collections_ids);

    return [ "data" => $collections_map ];
  }

  /**
   * find the SciVideo mapping id for a local site term
   * 
   * @param mixed $term_mappings
   * @param mixed $value
   */
  private function getSciVideosMappedTermId($term_mappings, $value) {
    $mappings = [];
    foreach ($term_mappings as $term) {
      if ($term['site_term_id'] == $value['target_id']) {
        return $term['scivideos_term_id'];
      }
    }
    return $mappings;
  }

  /**
   * find the Configuration file for a type (taxonomy)
   * 
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   */
  private function getConfigMappingFile(EntityReferenceFieldItemListInterface $type) {
    $hdl = $type->getFieldDefinition()->getSetting('handler_settings');
    $mapping_type = array_values($hdl['target_bundles'])[0];

    //need to load config by vocabulary types?? subjects, collection_type, talk_types... 
    $config_file = "scitalk_base.scitalk_base.{$mapping_type}";
    return $config_file;
  }

  /**
   * convert URIs to external links
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $link
   */
  private function getExternalUrl(FieldItemListInterface $link) {
    $url = Url::fromUri($link->uri, ['absolute' => TRUE])->toString() ?? '';
    return $url;
  }

  /**
   * map local keywords to their SciVideos records
   *
   * @param mixed $keywords
   */
  private function mapKeywords($keywords) {
    $keyword_ids = array_map(function($keyword) {
      if (empty($keyword)) {
        return [];
      }

      $target_id = $keyword['target_id'];
      $keyword_entity = $this->entityTypeManager->getStorage('taxonomy_term')->load($target_id);

      //try and fetch using the keyword name
      $name = $keyword_entity->name->value ?? '';
      if (!empty($name)) {
        $keyword_search = $this->talkKeyword->fetchByName($name);

        $keyword_search = json_decode($keyword_search);
        $found = $keyword_search->meta->count ?? count($keyword_search->data);
        if ($found > 0) {
          $uuid = current($keyword_search->data)->id;
          return $uuid;
        }
      }

      //otherwise, create keyword
      $new_keyword = $this->addTalkKeyword($keyword_entity);
      return $new_keyword->data->id ?? '';

    }, $keywords);

    //remove any empty
    $keyword_ids = array_filter($keyword_ids, function($keyword) {
        return !empty($keyword);
    });

    $keyword_ids = array_values($keyword_ids);

    if (empty(current($keyword_ids))) {
      return [ "data" => [] ];
    }

    $keywords_map = array_map(function($itm) {
        return ["type" => "taxonomy_term--talk_keywords",
                "id" => $itm
        ];
    }, $keyword_ids);

    return [ "data" => $keywords_map ];
  }

  /**
   * add SciVideos Talk Keyword
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  private function addTalkKeyword(EntityInterface $entity) {
    $keyword = $this->buildTalkKeyword($entity);

    try {
      $response = $this->talkKeyword->create($keyword);
      $scivideo_keyword = json_decode($response);
      return $scivideo_keyword;
    }
    catch (Exception $ex) {
      $this->messenger->addError("Something went wrong! " . $ex->getMessage());
    }
  }

  /**
   * create Talk Keyword SciVideo object
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  private function buildTalkKeyword(EntityInterface $entity) {
    $keyword = [
      "data" => [
            "type" => "taxonomy_term--talk_keywords",
            "attributes" => [
                "title" => $entity->title->value ?? '',
                "name" => $entity->name->value ?? '',
                "status" => $entity->status->value ?? TRUE,
                "description" => [
                    "value" => $entity->description->value ?? '',
                    "format" => "basic_html",
                ]
            ]
      ]
    ];

    return $keyword;
  }

}