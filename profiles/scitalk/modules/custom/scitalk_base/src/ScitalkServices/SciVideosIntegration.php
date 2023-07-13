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

class SciVideosIntegration {
  
  private const DOI_STATE_TO_FINDABLE = 'publish';
  private const DOI_STATE_FROM_FINDABLE_TO_REGISTER = 'hide';
  private const DOI_STATE_FROM_DRAFT_TO_REGISTER = 'register';

  private $scivideos_api_url;
  private $scivideos_api_client_id;
  private $scivideos_api_client_secret;
  private $scivideos_api_client_scope;

  public function __construct() {
    $config = \Drupal::config('scitalk_base.settings');

    $this->scivideos_api_url =  $config->get('scivideos_api_url');

    if (substr($this->scivideos_api_url, -1) != '/') {
      $this->scivideos_api_url .= '/';
    }

    $this->scivideos_api_client_id = $config->get('scivideos_api_client_id');
    $this->scivideos_api_client_secret = $config->get('scivideos_api_client_secret');
    $this->scivideos_api_client_scope = $config->get('scivideos_api_client_scope');
  }

  /**
   * add SciVideos Talk
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addTalk( EntityInterface $entity) {
    $doiObj = $this->buildTalk($entity);
    return $this->remoteAdd($doiObj);
  }

  /**
   * UpdateSciVideos Talk
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function updateTalk( EntityInterface $entity) {
    $doiObj = $this->buildTalk($entity);
    return $this->remoteUpdate($doiObj);
  }

  /**
   * Delete SciVideos Talk
   *
   * @param string doi
   */
  public function deleteTalk($uuid) {
    return $this->remoteDelete($uuid);
  }

  /**
   * add SciVideos Collection
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addCollection( EntityInterface $entity) {
    $doiObj = $this->buildCollection($entity);
    return $this->remoteAdd($doiObj);
  }

  /**
   * UpdateSciVideos Collection
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function updateCollection( EntityInterface $entity) {
    $doiObj = $this->buildCollection($entity);
    return $this->remoteUpdate($doiObj);
  }

  /**
   * Delete SciVideos Collection
   *
   * @param string uuid
   */
  public function deleteCollection($uuid) {
    return $this->remoteDelete($uuid);
  }

  /**
   * add SciVideos Speaker Profile
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function addSpeakerProfile( EntityInterface $entity) {
    $doiObj = $this->buildSpeakerProfile($entity);
    return $this->remoteAdd($doiObj);
  }

  /**
   * UpdateSciVideos Speaker Profile
   *
   * @param \Drupal\Core\Entity\EntityInterface entity
   */
  public function updateSpeakerProfile( EntityInterface $entity) {
    $doiObj = $this->buildTalk($entity);
    return $this->remoteUpdate($doiObj);
  }

  /**
   * Delete SciVideos Speaker Profile
   *
   * @param string doi
   */
  public function deleteSpeakerProfile($uuid) {
    return $this->remoteDelete($uuid);
  }

  
  private function remoteAdd($obj) {
  }

  private function remoteUpdate($obj) {

  }

  private function remoteDelete($obj) {

  }

  private function buildTalk($entity) {}
  private function buildCollection($entity) {}
  private function buildSpeakerProfile($entity) {}


}