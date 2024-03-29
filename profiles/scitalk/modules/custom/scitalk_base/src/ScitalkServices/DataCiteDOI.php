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

class DataCiteDOI {
  
  private const DOI_STATE_TO_FINDABLE = 'publish';
  private const DOI_STATE_FROM_FINDABLE_TO_REGISTER = 'hide';
  private const DOI_STATE_FROM_DRAFT_TO_REGISTER = 'register';

  private $doi_api_url;
  private $doi_prefix;
  private $datacite_user;
  private $datacite_pwd;
  private $datacite_creator_institution;
  private $datacite_creator_institution_ror;
  private $datacite_alternate_indentifier;

  public function __construct() {
    $config = \Drupal::config('scitalk_base.settings');

    $this->doi_api_url =  $config->get('doi_api_url');

    if (substr($this->doi_api_url, -1) != '/') {
      $this->doi_api_url .= '/';
    }

    $this->doi_prefix = $config->get('doi_prefix');
    $this->datacite_user = $config->get('datacite_user');
    $this->datacite_pwd = $config->get('datacite_pwd');
    $this->datacite_creator_institution = $config->get('datacite_creator_institution');
    $this->datacite_creator_institution_ror = !empty($config->get('datacite_creator_institution_ror')) ? 'https://ror.org/' . $config->get('datacite_creator_institution_ror') : '';

    $this->datacite_alternate_indentifier = $config->get('datacite_alternate_indentifier');
    if (!empty($this->datacite_alternate_indentifier) && substr($this->datacite_alternate_indentifier, -1) != '/') {
      $this->datacite_alternate_indentifier .= '/';
    }
  }


  /**
   * create DOI Draft
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function create( EntityInterface $entity) {
    $doiObj = $this->buildDOIObject($entity);
    return $this->createDOI($doiObj);
  }

  /**
   * Update DOI to either "Registed" or "FIndable" based on media status in the Talk
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function update( EntityInterface $entity) {
    $doiObj = $this->buildDOIObject($entity);
    return $this->updateDOI($doiObj);
  }

  /**
   * Delete DOI by id
   *
   * @param string doi
   */
  public function delete($doi) {
    return $this->deleteDOI($doi);
  }

  /**
   * Fetch DOI by id
   *
   * @param string doi
   */
  public function getDOI($doi) {
    return $this->fetchDOIByID($doi);
  }

  /**
   * Fetch DOI by Talk ID
   *
   * @param string talk_id
   */
  public function getDOIByTalkId($talk_id) {
    $doi = $this->doi_prefix . '/' . $talk_id;
    return $this->fetchDOIByID($doi);
  }

  private function createDOI($doiObj) {
    $url = $this->doi_api_url;
    $client = \Drupal::httpClient();

    $doi_id = '';
    $params = [
      'auth' => [$this->datacite_user,$this->datacite_pwd], 
      'json' => $doiObj
    ];

    try {
      $request = $client->post($url, $params);
      $response = $request->getBody();
      $response = json_decode($response);
      $doi_id = $response->data->id;

      \Drupal::logger('scitalk_base')->notice('DOI CREATED: ' .$doi_id);
    }
    catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
      if (!empty($res = $e->getResponse()->getBody()->getContents())) {
        $err = json_decode($res);
        $msg = 'DOI create error: ' . ( $err->errors[0]->title ?? '');
        \Drupal::messenger()->addError(t($msg));
      }
      
      \Drupal::logger('scitalk_base')->error('DOI ERROR: ' . print_r($e->getMessage() , TRUE) );
    }
    finally {
      return $doi_id;
    }
  }

  private function updateDOI($doiObj) {
    $doi_id = $doiObj['data']['id'];
    $url = $this->doi_api_url . $doi_id;
   
    $client = \Drupal::httpClient();

    $params = [
      'auth' => [$this->datacite_user,$this->datacite_pwd],
      'json' => $doiObj
    ];

    try {
      $request = $client->put($url, $params);
      $response = $request->getBody();
      $response = json_decode($response);
      $doi_id = $response->data->id;

      \Drupal::logger('scitalk_base')->notice('UPDATED DOI : ' .$doi_id);
    }
    catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
      if (!empty($res = $e->getResponse()->getBody()->getContents())) {
        $err = json_decode($res);
        $msg = 'DOI update error: ' . ( $err->errors[0]->title ?? '');
        \Drupal::messenger()->addError(t($msg));
      }
      
      \Drupal::logger('scitalk_base')->error('DOI ERROR: ' . print_r($e->getMessage() , TRUE) );
    }
    finally {
      return $doi_id;
    }

  }

  private function deleteDOI($doi_id) {
    $url = $this->doi_api_url . $doi_id;
    $client = \Drupal::httpClient();

    $params = [
      'auth' => [$this->datacite_user,$this->datacite_pwd]
    ];

    try {
      $request = $client->delete($url, $params);

      $msg = "DOI {$doi_id} deleted.";
      \Drupal::messenger()->addMessage(t($msg));
    }
    catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
      if (!empty($res = $e->getResponse()->getBody()->getContents())) {
        $err = json_decode($res);
        $msg = 'DOI delete error: ' . ( $err->errors[0]->title ?? '');
        $msg .= "<br>(Perhaps DOI {$doi_id} has already been Registered and cannot be deleted)";
        \Drupal::messenger()->addError(t($msg));
      }

      \Drupal::logger('scitalk_base')->error('DOI ERROR: ' . print_r($e->getMessage() , TRUE) );
    }

  }

  private function buildDOIObject($entityObj) {
    $entity = $entityObj->getTypedData();

    $talk_number = $entity->get('field_talk_number')->getValue();
    $abstract = $entity->get('field_talk_abstract')->getValue();
    $talk_id = $this->doi_prefix . '/' . $entity->get('field_talk_number')->value;
    //$url = $entityObj->toUrl()->setAbsolute()->toString(); 
    $url = $entityObj->toUrl()->setAbsolute()->toString(true)->getGeneratedUrl() ?? '';

    /////////////////////
    /////////////////////
    // TODO: REMOVE THE LINE BELOW AFTER DONE RUNNING THe create_interimhd_media_sript.php!!!! 
    //$url = str_replace( '/create_interimhd_media_script.php', '', $url);  //for now when running update script!!!!
    //$reference_number = $entity->field_talk_number->value; 

    $publisher = $this->datacite_creator_institution ?? '';
    $repo_id = $entity->get('field_talk_source_repository')->target_id ?? '';
    if (!empty($repo_id)) {
      $repo = Group::load($repo_id);
      $publisher = $repo->field_repo_institution_full_name->value ?? $publisher;
    }

    $data = [
      'id' => $talk_id,
      'type' => 'dois',
      'attributes' => [
        'doi' => $talk_id,
        'publisher' =>  $publisher,
        'titles' => [
          ['title' => $entity->get('title')->value ?? '']
        ],
        'descriptions' => [
          'description' =>  $entity->get('field_talk_abstract')->value ?? ''
          //'description' => strip_tags( $entity->get('field_talk_abstract')->value ?? '')
        ],
        'types' => [
          'resourceTypeGeneral' => 'Audiovisual',
          'resourceType' => 'Video Recording'
        ],
        'formats' => [
          'video/mp4'
        ],
        'url' => $url,
        'language' => \Drupal::languageManager()->getDefaultLanguage()->getName() ?? '',
        'schemaVersion' => 'http://datacite.org/schema/kernel-4'
      ]
    ];

    //speaker info (use the institution name instead of the talk speaker) - NO!: updated this to use talk speakers:
    $speakerProfile = $this->getSpeakerInfo($entity->get('field_talk_speaker_profile')->getValue());
    //$speakerProfile =  $this->getCreator();
    if (!empty($speakerProfile)) {
      $data['attributes']['creators'] = $speakerProfile;
    }

    //publication date info
    $pubDate = $entity->get('field_talk_date')->value ?? '';
    if (!empty($pubDate)) {
      $pubYear = date('Y', strtotime($pubDate));
      $data['attributes']['publicationYear'] = $pubYear;
      $data['attributes']['dates'] = [
        'date' => $pubDate,
        'dateType' => 'Created'
      ];
    }

    $subjects = $this->getSubject($entity);
    if (!empty($subjects)) {
      $data['attributes']['subjects'] = $subjects;
    }

    /*
      check if media available in the talk and if so then set the DOI status to Findable or Registered
        e.g.   event="register" / event="publish"  (maybe isActive=true/false)
     Possible actions when publishing to findable:
        publish - Triggers a state move from draft or registered to findable
        register - Triggers a state move from draft to registered
        hide - Triggers a state move from findable to registered
    */
    if (!empty($entity->get('field_talk_video')->target_id)) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load( $entity->get('field_talk_video')->target_id);
      $data['attributes']['event'] = self::DOI_STATE_TO_FINDABLE;
    }

    if (!empty($this->datacite_alternate_indentifier)) {
      $alternate_identifier_url = $this->datacite_alternate_indentifier . $entity->get('field_talk_number')->value;
      $data['attributes']['identifiers'] = [
        [
          'identifier' => $alternate_identifier_url,
          'identifierType' => 'PURL'
        ]
      ];
    }

    //create "Related Identifiers" from DOI and arXiv attachments
    $talk_attachments = $entity->get('field_talk_attachments');
    if (!empty($talk_attachments)) {
      $related = [];
      foreach($talk_attachments->referencedEntities() as $attach) {
        $attachment_id = $attach->get('name')->value ?? '';
        $relationship_type = 'References'; //'IsReferencedBy'

        switch ($attach->bundle()) {
          case 'doi';
            $related[] = [
              'relatedIdentifierType' => 'DOI',
              'relationType' => $relationship_type,
              'relatedIdentifier' => $attachment_id
            ];
            break;
          case 'arxiv':
            $related[] = [
              'relatedIdentifierType' => 'arXiv',
              'relationType' => $relationship_type,
              'relatedIdentifier' => 'arXiv:' . $attachment_id
            ];
            break;
        }
      }

      $data['attributes']['relatedIdentifiers'] = $related;
    }

    return ['data' => $data];
  }

  private function fetchDOIByID($doi_id) {
    $url = $this->doi_api_url . urlencode($doi_id);

    $params = [
      'auth' => [$this->datacite_user,$this->datacite_pwd]
    ];

    $client = \Drupal::httpClient();

    $response = NULL;
    try {
      $request = $client->get($url, $params);
      $response = $request->getBody();
    }
    catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
      if (!empty($res = $e->getResponse()->getBody()->getContents())) {
        $err = json_decode($res);
        if ($err->errors[0]->status != 404) {//if error is other than not found then log this error
          $msg = 'DOI Fetch: ' . ( $err->errors[0]->title ?? '');
          \Drupal::logger('scitalk_base')->error($msg);
        }
      }
      else {
        \Drupal::logger('scitalk_base')->error('DOI Fetch: ' . print_r($e->getMessage() , TRUE));
      }
    }
    finally {
      return $response;
    }
    
  }

  //we are going to use the institution for the Creator field in DOI instead of the talk speakers:
  private function getCreator() {
      $speakers[] = [
        'name' =>  $this->datacite_creator_institution, //'Perimeter Institute',
        'nameType' => 'Organizational',
        'affiliation' => [ 
          [
            'name' => $this->datacite_creator_institution,
            'schemeUri' => 'https://ror.org',
            'affiliationIdentifier' => $this->datacite_creator_institution_ror,
            'affiliationIdentifierScheme' => 'ROR'
          ] 
        ]
      ];
      return $speakers;
  }

  private function getSpeakerInfo($speakersObj) {
    //if no speaker return the institution
    if (empty($speakersObj)) {
      return $this->getCreator();  
    }

    $speakers = [];
    foreach ($speakersObj as $sp) {
      $tid = $sp['target_id'];
      $speakerProfile = \Drupal::entityTypeManager()->getStorage('node')->load($tid);
      $speakers[] = [
        'nameType' => 'Personal',
        'givenName' => $speakerProfile->field_sp_first_name->value ?? '',
        'familyName' => $speakerProfile->field_sp_last_name->value ?? 'unknown',
        'affiliation' => [ ['name' => $speakerProfile->field_sp_institution_name->value ?? ''] ]
      ];
    }
    return $speakers;
  }

  //return values in Scientific area and Keyword fields to fill DOI subject
  private function getSubject($entity) {
    $subjects = [];
    $sareas = $entity->get('field_scientific_area')->getValue();
    foreach ($sareas as $sa) {
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($sa['target_id']);
      $subjects[] = ['subject' => $term->getName()];
    }

    $keywords = $entity->get('field_talk_keywords')->getValue();
    foreach ($keywords as $key) {
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($key['target_id']);
      $subjects[] = ['subject' => $term->getName()];
    }

    return $subjects;
  }

 
}