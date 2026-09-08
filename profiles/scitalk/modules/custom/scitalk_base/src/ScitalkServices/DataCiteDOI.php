<?php

namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\group\Entity\Group;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;

/**
 * This class updates DataCite DOI info.
 */
class DataCiteDOI {

  private const DOI_STATE_TO_FINDABLE = 'publish';
  private const DOI_STATE_FROM_FINDABLE_TO_REGISTER = 'hide';
  private const DOI_STATE_FROM_DRAFT_TO_REGISTER = 'register';

  /**
   * DataCite REST API url.
   *
   * @var string
   */
  private string $doiApiUrl;

  /**
   * DataCite DOI prefix.
   *
   * @var string
   */
  private string $doiPrefix;

  /**
   * DataCite REST API user.
   *
   * @var string
   */
  private string $dataciteUser;

  /**
   * DataCite REST API user password.
   *
   * @var string
   */
  private string $datacitePwd;

  /**
   * DataCite DOI creator institution.
   *
   * @var string
   */
  private string $dataciteCreatorInstitution;

  /**
   * DataCite DOI creator institution ROR.
   *
   * @var string
   */
  private string $dataciteCreatorInstitutionRor;

  /**
   * DataCite DOI alternate identifier.
   *
   * @var string
   */
  private string $dataciteAlternateIndentifier;

  public function __construct() {
    $config = \Drupal::config('scitalk_base.settings');

    $this->doiApiUrl = $config->get('doi_api_url');

    if (substr($this->doiApiUrl, -1) != '/') {
      $this->doiApiUrl .= '/';
    }

    $this->doiPrefix = $config->get('doi_prefix');
    $this->dataciteUser = $config->get('datacite_user');
    $this->datacitePwd = $config->get('datacite_pwd');
    $this->dataciteCreatorInstitution = $config->get('datacite_creator_institution');
    $this->dataciteCreatorInstitutionRor = !empty($config->get('datacite_creator_institution_ror')) ? 'https://ror.org/' . $config->get('datacite_creator_institution_ror') : '';

    $this->dataciteAlternateIndentifier = $config->get('datacite_alternate_indentifier');
    if (!empty($this->dataciteAlternateIndentifier) && substr($this->dataciteAlternateIndentifier, -1) != '/') {
      $this->dataciteAlternateIndentifier .= '/';
    }
  }

  /**
   * Create DOI Draft.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which we need to create the DOI data.
   */
  public function create(ContentEntityInterface $entity) {
    $doiObj = $this->buildDOIObject($entity);
    return $this->createDOI($doiObj);
  }

  /**
   * Update DOI state (Registed or Findable) based on media status in the Talk.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which we need to update the DOI data.
   */
  public function update(ContentEntityInterface $entity) {
    $doiObj = $this->buildDOIObject($entity);
    return $this->updateDOI($doiObj);
  }

  /**
   * Delete DOI by id.
   *
   * @param string $doi
   *   The DOI id to delete.
   */
  public function delete($doi) {
    return $this->deleteDOI($doi);
  }

  /**
   * Fetch DOI by id.
   *
   * @param string $doi
   *   The DOI to fetch.
   */
  public function getDOI($doi) {
    return $this->fetchDOIById($doi);
  }

  /**
   * Fetch DOI by Talk ID.
   *
   * @param string $talk_id
   *   The talk id for which to fetch the DOI info.
   */
  public function getDOIByTalkId($talk_id) {
    $doi = $this->doiPrefix . '/' . $talk_id;
    return $this->fetchDOIById($doi);
  }

  /**
   * Create a DOI entry in DataCite.
   *
   * @param mixed $doiObj
   *   The DOI object containing the data to send to DataCite.
   */
  private function createDOI($doiObj) {
    $url = $this->doiApiUrl;
    $client = \Drupal::httpClient();

    $doi_id = '';
    $params = [
      'auth' => [$this->dataciteUser, $this->datacitePwd],
      'json' => $doiObj,
    ];

    try {
      $request = $client->post($url, $params);
      $response = $request->getBody();
      $response = json_decode($response);
      $doi_id = $response->data->id;

      \Drupal::logger('scitalk_base')->notice('DOI created: ' . $doi_id);
    }
    catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
      if (!empty($res = $e->getResponse()->getBody()->getContents())) {
        $err = json_decode($res);
        $msg = 'DOI create error: ' . ($err->errors[0]->title ?? '');
        \Drupal::messenger()->addError($msg);
      }

      \Drupal::logger('scitalk_base')->error('DOI ERROR: ' . print_r($e->getMessage(), TRUE));
    }
    finally {
      return $doi_id;
    }
  }

  /**
   * Update a DOI entry in DataCite.
   *
   * @param mixed $doiObj
   *   The DOI object containing the data to send to DataCite.
   */
  private function updateDOI($doiObj) {
    $doi_id = $doiObj['data']['id'];
    $url = $this->doiApiUrl . $doi_id;

    $client = \Drupal::httpClient();

    $params = [
      'auth' => [$this->dataciteUser, $this->datacitePwd],
      'json' => $doiObj,
    ];

    try {
      $request = $client->put($url, $params);
      $response = $request->getBody();
      $response = json_decode($response);
      $doi_id = $response->data->id;

      \Drupal::logger('scitalk_base')->notice('DOI updated: ' . $doi_id);
    }
    catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
      if (!empty($res = $e->getResponse()->getBody()->getContents())) {
        $err = json_decode($res);
        $msg = 'DOI update error: ' . ($err->errors[0]->title ?? '');
        \Drupal::messenger()->addError($msg);
      }

      \Drupal::logger('scitalk_base')->error('DOI ERROR: ' . print_r($e->getMessage(), TRUE));
    }
    finally {
      return $doi_id;
    }

  }

  /**
   * Delete a DOI entry from DataCite.
   *
   * @param string $doi_id
   *   The DOI id to delete from DataCite.
   */
  private function deleteDOI($doi_id) {
    $url = $this->doiApiUrl . $doi_id;
    $client = \Drupal::httpClient();

    $params = [
      'auth' => [$this->dataciteUser, $this->datacitePwd],
    ];

    try {
      $request = $client->delete($url, $params);

      $msg = "DOI {$doi_id} deleted.";
      \Drupal::messenger()->addMessage($msg);
    }
    catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
      if (!empty($res = $e->getResponse()->getBody()->getContents())) {
        $err = json_decode($res);
        $msg = 'DOI delete error: ' . ($err->errors[0]->title ?? '');
        $msg .= "<br>(Perhaps DOI {$doi_id} has already been Registered and cannot be deleted)";
        \Drupal::messenger()->addError($msg);
      }

      \Drupal::logger('scitalk_base')->error('DOI ERROR: ' . print_r($e->getMessage(), TRUE));
    }

  }

  /**
   * Build a DataCite object from an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entityObj
   *   The entity from which to build the DataCite object.
   */
  private function buildDOIObject(ContentEntityInterface $entityObj): array {
    $entity = $entityObj->getTypedData();

    $talk_number = $entity->get('field_talk_number')->value ?? '';
    $abstract = $entity->get('field_talk_abstract')->value ?? '';
    $talk_id = $this->doiPrefix . '/' . $talk_number;
    $title = $entity->get('title')->value ?? '';
    $url = $entityObj->toUrl()->setAbsolute()->toString(TRUE)->getGeneratedUrl() ?? '';

    $publisher = $this->dataciteCreatorInstitution ?? '';
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
        'publisher' => $publisher,
        'titles' => [
          ['title' => $title],
        ],
        'descriptions' => [
          [
            'description' => $abstract,
            'descriptionType' => 'Abstract',
          ],
        ],
        'types' => [
          'resourceTypeGeneral' => 'Audiovisual',
          'resourceType' => 'Video Recording',
        ],
        'formats' => [
          'video/mp4',
        ],
        'url' => $url,
        'language' => \Drupal::languageManager()->getDefaultLanguage()->getName() ?? '',
        'schemaVersion' => 'http://datacite.org/schema/kernel-4',
      ],
    ];

    // Speaker info:
    $speakerProfile = $this->getSpeakerInfo($entity->get('field_talk_speaker_profile')->getValue());
    if (!empty($speakerProfile)) {
      $data['attributes']['creators'] = $speakerProfile;
    }

    // Publication date info.
    $pubDate = $entity->get('field_talk_date')->value ?? '';
    if (!empty($pubDate)) {
      $pubYear = date('Y', strtotime($pubDate));
      $data['attributes']['publicationYear'] = $pubYear;
      $data['attributes']['dates'] = [
        'date' => $pubDate,
        'dateType' => 'Created',
      ];
    }
    else {
      $data['attributes']['dates'] = [];
    }

    $subjects = $this->getSubject($entity);
    $data['attributes']['subjects'] = $subjects;

    /*
    Check if media available in the talk,
    and if so then set the DOI status to Findable or Registered
      e.g.   event="register" / event="publish"  (maybe isActive=true/false)
    Possible actions when publishing to findable:
      publish - Triggers a state move from draft or registered to findable
      register - Triggers a state move from draft to registered
      hide - Triggers a state move from findable to registered
     */
    if (!empty($entity->get('field_talk_video')->target_id)) {
      // $media = \Drupal::entityTypeManager()->getStorage('media')->load($entity->get('field_talk_video')->target_id);
      $data['attributes']['event'] = self::DOI_STATE_TO_FINDABLE;
    }

    if (!empty($this->dataciteAlternateIndentifier)) {
      $alternate_identifier_url = $this->dataciteAlternateIndentifier . $talk_number;
      $data['attributes']['identifiers'] = [
        [
          'identifier' => $alternate_identifier_url,
          'identifierType' => 'PURL',
        ],
      ];
    }
    else {
      $data['attributes']['identifiers'] = [];
    }

    // Create "Related Identifiers" from DOI and arXiv attachments.
    $talk_attachments = $entity->get('field_talk_attachments');
    $related = [];
    if (!empty($talk_attachments)) {
      foreach ($talk_attachments->referencedEntities() as $attach) {
        $attachment_id = $attach->get('name')->value ?? '';
        // 'IsReferencedBy'
        $relationship_type = 'References';

        switch ($attach->bundle()) {
          case 'doi':
            $related[] = [
              'relatedIdentifierType' => 'DOI',
              'relationType' => $relationship_type,
              'relatedIdentifier' => $attachment_id,
            ];
            break;

          case 'arxiv':
            $related[] = [
              'relatedIdentifierType' => 'arXiv',
              'relationType' => $relationship_type,
              'relatedIdentifier' => 'arXiv:' . $attachment_id,
            ];
            break;
        }
      }
    }
    $data['attributes']['relatedIdentifiers'] = $related;

    return ['data' => $data];
  }

  /**
   * Fetch a DOI from DataCite.
   *
   * @param string $doi_id
   *   The DOI id to fetch.
   */
  private function fetchDOIById($doi_id) {
    $url = $this->doiApiUrl . urlencode($doi_id);

    $params = [
      'auth' => [$this->dataciteUser, $this->datacitePwd],
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
        // If error is other than not found then log this error.
        if ($err->errors[0]->status != 404) {
          $msg = 'DOI Fetch: ' . ($err->errors[0]->title ?? '');
          \Drupal::logger('scitalk_base')->error($msg);
        }
      }
      else {
        \Drupal::logger('scitalk_base')->error('DOI Fetch: ' . print_r($e->getMessage(), TRUE));
      }
    }
    finally {
      return $response;
    }

  }

  /**
   * Build the Creator data from the institution field.
   */
  private function getCreator(): array {
    $speakers[] = [
      'name' => $this->dataciteCreatorInstitution,
      'nameType' => 'Organizational',
      'affiliation' => [
          [
            'name' => $this->dataciteCreatorInstitution,
            'schemeUri' => 'https://ror.org',
            'affiliationIdentifier' => $this->dataciteCreatorInstitutionRor,
            'affiliationIdentifierScheme' => 'ROR',
          ],
      ],
    ];
    return $speakers;
  }

  /**
   * Build DataCite speaker data.
   *
   * @param mixed $speakersObj
   *   An object list with the speakers info.
   */
  private function getSpeakerInfo($speakersObj): array {
    // If no speaker return the institution.
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
        'affiliation' => [['name' => $speakerProfile->field_sp_institution_name->value ?? '']],
      ];
    }
    return $speakers;
  }

  /**
   * Build DOI subjects from Subject and Keyword fields.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $entity
   *   An entity object from which to extract the subject data.
   */
  private function getSubject(ComplexDataInterface $entity): array {
    $doi_subjects = [];
    $added = [];
    // Include subject and keywords fields in the DOI's subject:
    // $sareas = $entity->get('field_scientific_area')->getValue();
    $subjects = $entity->get('field_talk_subject')->getValue();
    $keywords = $entity->get('field_talk_keywords')->getValue();
    $all_subjects = [...$subjects, ...$keywords];
    foreach ($all_subjects as $subject) {
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($subject['target_id']);
      $term_name = $term->getName();
      // Make sure names are unique
      if (!in_array($term_name, $added)) {
        $added[] = $term_name;
        $doi_subjects[] = ['subject' => $term_name];
      }
    }

    return $doi_subjects;
  }

}
