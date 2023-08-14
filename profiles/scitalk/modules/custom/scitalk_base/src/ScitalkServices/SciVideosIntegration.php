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

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;
use Drupal\scitalk_base\SciVideosIntegration\Entities\Talk;
use Drupal\scitalk_base\SciVideosIntegration\Entities\SpeakerProfile;
use Drupal\scitalk_base\SciVideosIntegration\Entities\Collection;
use Drupal\scitalk_base\SciVideosIntegration\Entities\Vocabularies;
use Drupal\scitalk_base\SciVideosIntegration\Entities\VocabularyTerms;

class SciVideosIntegration {

  private $configFactory;
  private $scivideos;
  private $speakerProfile;
  private $talk;
  private $collection;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;

    $this->scivideos = SciVideosAuthentication::getInstance();
    $this->talk = new Talk($this->scivideos);
    $this->speakerProfile = new SpeakerProfile($this->scivideos);
    $this->collection = new Collection($this->scivideos);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  public function fetchVocabularies() {
    $vobularies = new Vocabularies($this->scivideos);
    return $vobularies->fetch();
  }

  public function fetchVocabularyTerms($vocabulary_name) {
    $vobularies = new VocabularyTerms($this->scivideos, $vocabulary_name);
    return $vobularies->fetch();
  }

  /**
   * add SciVideos Talk
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addTalk( EntityInterface $entity) {
    $talk = $this->buildTalk($entity);

// ksm($entity->toArray(), 'create Talk entity');
// ksm($talk, 'build talk object');

    $response = $this->talk->create($talk);
    $scivideo_talk = json_decode($response);

    // set integration id
    if (!empty($entity->field_scivideos_uuid)) {
      unset($entity->field_scivideos_uuid);
    }

  // ksm($scivideo_talk, 'got this');

    $entity->set('field_scivideos_uuid', $scivideo_talk->data->id);
    $entity->save();

    return $scivideo_talk;
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

    // ksm($entity->toArray(), 'build talk');
    // ksm($talk, 'built');

    $response = $this->talk->update($talk);
    $scivideo_talk = json_decode($response);

    // ksm($scivideo_talk, 'SCIVideos Integration UPDATED a remote talks');

    return $scivideo_talk;

  }

  /**
   * Delete SciVideos Talk
   *
   * @param string doi
   */
  public function deleteTalk(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    $talk = $this->buildTalk($entity);
    $response = $this->talk->delete($talk);
    $scivideo_talk = json_decode($response);
    return $scivideo_talk;
  }

  /**
   * add SciVideos Collection
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addCollection( EntityInterface $entity) {
    $collection = $this->buildCollection($entity);

    $response = $this->collection->create($collection);
    $scivideo_collection = json_decode($response);

    // set integration id
    if (!empty($entity->field_scivideos_uuid)) {
      unset($entity->field_scivideos_uuid);
    }

  // ksm($scivideo_collection, 'got this collection from scivideso');
    $entity->set('field_scivideos_uuid', $scivideo_collection->data->id);
    $entity->save();

    return $scivideo_collection;
  }

  /**
   * UpdateSciVideos Collection
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function updateCollection( EntityInterface $entity) {
    // ksm($entity->toArray(), 'build this collection');

    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    $collection = $this->buildCollection($entity);

    $response = $this->collection->update($collection);
    $scivideo_collection = json_decode($response);

    return $scivideo_collection;
  }

  /**
   * Delete SciVideos Collection
   *
   * @param string uuid
   */
  public function deleteCollection(EntityInterface $entity) {
    $scivideos_uuid = $entity->field_scivideos_uuid->value ?? '';
    if (empty($scivideos_uuid)) {
      return;
    }

    $collection = $this->buildCollection($entity);
    $response = $this->collection->delete($collection);
    $scivideo_collection = json_decode($response);
    return $scivideo_collection;
  }

  /**
   * add SciVideos Speaker Profile
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addSpeakerProfile( EntityInterface $entity) {
    $speaker = $this->buildSpeakerProfile($entity);

    $response = (new SpeakerProfile($this->scivideos))->create($speaker);
    $scivideo_speaker = json_decode($response);

    \Drupal::logger('scitalk_base')->notice('SCIVideos Integration response from remote - Create Spealker: <pre>' . print_r($scivideo_speaker , TRUE) . '</pre>' );

    // set integration id
    if (!empty($entity->field_scivideos_uuid)) {
      unset($entity->field_scivideos_uuid);
    }
    $entity->set('field_scivideos_uuid', $scivideo_speaker->data->id);
    $entity->save();

    return $scivideo_speaker;
  }

  /**
   * UpdateSciVideos Speaker Profile
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function updateSpeakerProfile( EntityInterface $entity) {
    $doiObj = $this->buildTalk($entity);
    return [];
  }

  /**
   * Delete SciVideos Speaker Profile
   *
   * @param string uuid
   */
  public function deleteSpeakerProfile($uuid) {
    return [];
  }


  private function buildTalk($entity) {
    $pirsa_repo_id = '08adc543-925a-4f74-a63c-91ec55b68669';
    $pirsa_subject_id = '12f6b2f2-35d6-43b6-9529-00404e199e4a';  //Physics - for all PIRSA talks

    $pirsa_url = 'http://pirsa.org/';

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
              "field_talk_date" => $this->formatDate( $entity->field_talk_date->value ),
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

    $talk_url = $entity->toUrl()->setAbsolute()->toString(true)->getGeneratedUrl() ?? '';
    $talk["data"]["attributes"]["field_talk_video_url"] = [
      "uri" => $talk_url,
      "title" => $talk_url,
      "options" => ['attributes' => ['target' => '_blank'] ]
    ];

    $speakers = $entity->get('field_talk_speaker_profile')->getValue() ?? [];
    if (!empty($speakers)) {
      $mapped_speakers = $this->mapSpeakers($speakers);
      $talk["data"]["relationships"]["field_talk_speaker_profile"] = $mapped_speakers;
    }
    else {  //make sure to force delete all speakers when none is set
      $talk["data"]["relationships"]["field_talk_speaker_profile"] = [ "data" => [] ];
    }

    //Group - Souce Repo
    $talk["data"]["relationships"]["field_talk_source_repository"] = $this->getSciVideosSourceRepository();

    $talk["data"]["relationships"]["field_talk_type"] = $this->mapTalkType($entity->get('field_talk_type'));
    $talk["data"]["relationships"]["field_talk_collection"] = $this->mapCollection($entity->get('field_talk_collection')->getValue());
    // $talk["data"]["relationships"]["field_talk_subject"] = [];
    // $talk["data"]["relationships"]["field_scientific_area"] = [];

    \Drupal::logger('scitalk_base')->notice('the created Talk mapped object <pre>' . print_r($talk , TRUE) . '</pre>' );
    return $talk;
  }

  private function buildCollection(EntityInterface $entity) {
    $collection = [
      "data" => [
        "type" => "collection",
        "attributes" => [
            "title" => $entity->title->value,
            "field_collection_number" => $entity->field_collection_number->value ?? '',
            "field_collection_description" => [
              "value" => stripslashes(stripslashes($entity->field_collection_description->value)) ?? '',
              "format" => "basic_html"
            ],
            "field_collection_short_desc" => [
              "value" => $entity->field_collection_short_desc->value ?? '',
              "format" => "basic_html"
            ],
            "field_collection_date" => [
               "value" => $this->formatDate( $entity->field_collection_date->value ) ?? NULL,
               "end_value" => $this->formatDate( $entity->field_collection_date->end_value ) ?? NULL,
            ],
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

    if (!empty($entity->field_collection_event_url)) {
      $collection["data"]["attributes"]["field_collection_event_url"] = [
        "uri" => $entity->field_collection_event_url->uri,
        "title" => $entity->field_collection_event_url->title,
        // "options"=> ['attributes' => ['target' => '_blank'] ]
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
    
    //Group - Souce Repo
    $collection["data"]["relationships"]["field_collection_source_repo"] = $this->getSciVideosSourceRepository();

    $collection["data"]["relationships"]["field_collection_subject"] = $this->mapSubjects($entity->get('field_collection_subject')->getValue());
    $collection["data"]["relationships"]["field_collection_type"] = $this->mapCollectionType($entity->get('field_collection_type')->getValue());
    $collection["data"]["relationships"]["field_parent_collection"] = $this->mapCollection($entity->get('field_parent_collection')->getValue());

    return $collection;
  }

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

    return $speaker_profile;
  }

  private function mapSpeakers($speakers) {
    $speaker_ids = array_map(function($speaker) {
      if (empty($speaker)) {
        return [];
      }

      $target_id = $speaker['target_id'];
      $speaker_entity = \Drupal::entityTypeManager()->getStorage('node')->load($target_id);

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
  
  private function formatDate($utc_date = '', $format = 'Y-m-d\TH:i:sP', $timezone = '') {
    if (empty($utc_date)) {
      return '';
    }
    $formatter = \Drupal::service('date.formatter');
    $drupal_date = new \Drupal\Core\Datetime\DrupalDateTime($utc_date, 'UTC');
    $timestamp = strtotime($drupal_date);
    if (empty($timezone)) {
      $timezone = date_default_timezone_get();
    }
    return $formatter->format($timestamp, 'custom', $format, $timezone);
  }

  private function getSciVideosSourceRepository() {
    $pirsa_repo = '08adc543-925a-4f74-a63c-91ec55b68669'; //PIRSA
    $cern_repo =  '801a572f-f652-1187-bc75-7161bc7a35df'; //CERN

    return [ 
      "data" => [
        "type" => "group--source_repository",
        "id" => $pirsa_repo
      ]
    ];
  }

  private function mapTalkType($talk_type = []) {
    //need to load config by vocabulary types?? subjects, collection_type, talk_types... 
    // $config_file = "scitalk_base.scitalk_base.{$mapping_type}";
    // $config = $this->configFactory->get($config_file);

    return ['data' => []];
  }

  private function mapCollectionType($collection_types = []) {
    return ['data' => []];
  }

  private function mapSubjects($subjects = []) {
    return ['data' => []];
  }

  private function mapCollection($collections = []) {
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
      $collection_entity = \Drupal::entityTypeManager()->getStorage('node')->load($target_id);

      $uuid = $collection_entity->field_scivideos_uuid->value ?? '';
      if (!empty($uuid)) {
        return $uuid;
      }

      //if not scivideo uuid, then fetch it from SciVideos using the collection number, then update parent uuid
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
  
  // private function formatDate($date_input) {
  //   $utc = new \DateTimeZone('UTC');
  //   // $user_tz = date_default_timezone_get();
  //   // $tz = new \DateTimeZone( $user_tz );
  //   // $date = new \DateTime(  $date_input, $tz);
  //   $date = new \DateTime($date_input, $utc);
  
  //   $date = $date->format("Y-m-d\TH:i:sP");
  //   return $date;
  // }
}