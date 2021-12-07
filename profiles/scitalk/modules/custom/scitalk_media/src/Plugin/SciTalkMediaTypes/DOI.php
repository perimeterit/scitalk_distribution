<?php

namespace Drupal\scitalk_media\Plugin\SciTalkMediaTypes;

use Drupal\scitalk_media\SciTalkMediaPluginBase;

use GuzzleHttp\Exception\GuzzleException;  
use GuzzleHttp\Exception\ConnectException;  
use GuzzleHttp\Exception\ClientException;  
use GuzzleHttp\Exception\ServerException;  
use GuzzleHttp\Exception\BadResponseException;  
use GuzzleHttp\Exception\RequestException;  

/**
 * SciTalk DOI Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this media type.  
 *
 * @Plugin(
 *   id = "SciTalkMediaDOI",
 *   description = @Translation("The DOI plugin for handling various SciTalk Media type functions."),
 *   media_type = "scitalk_doi",
 *   media_source = "",
 * )
 */
class DOI extends SciTalkMediaPluginBase {
   
  
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'SciTalkMediaDOI';
  }

  
  
  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\scitalk_media\SciTalkMediaPluginBase::entityInsert()
   */
  public function entityInsert() {
    $this->entityMetaDataUpdate();
  }
  
  
  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\scitalk_media\SciTalkMediaPluginBase::entityMetaDataUpdate()
   */
  public function entityMetaDataUpdate() {
    //fetch off media information
    $source = $this->entity->bundle->entity->getSource();
    $configuration = $source->getConfiguration();
    $val = $this->entity->{$configuration['source_field']}->getString();

    $crossref = \Drupal::service('scitalk_base.crossref_dois');
    $res = $crossref->getDOI($val);

    $doi = json_decode($res);
    $OK = !empty($doi->status) && $doi->status == 'ok';
    if ($OK) {
      $title = current($doi->message->title) ?? ''; 

      $authors = '';
      if (!empty($doi->message->author)) {
        $authors = implode(', ', array_map(function($itm){
          return $itm->given . ' ' . $itm->family;
        }, $doi->message->author));
      }

      $journal_name = $doi->message->{'container-title'} ?? '';
      $date_issued = '';
      if (!empty($doi->message->issued)) {
        $date_issued = implode('-' , current($doi->message->issued->{'date-parts'}));
        $date_issued = date('Y-m-d', strtotime($date_issued));
      }

      $date_published_online = '';
      if (!empty($doi->message->{'published-online'})) {
        $date_published_online = implode('-' , current( $doi->message->{'published-online'}->{'date-parts'}));
        $date_published_online = date('Y-m-d', strtotime($date_published_online));
      }

      $abstract = '';
      if (!empty($doi->message->abstract)) {
        $abstract = strip_tags($doi->message->abstract);
      }

      $page_range = $doi->message->page ?? '';
      $vol = $doi->message->volume ?? '';
      $issue = $doi->message->issue ?? '';
      $publisher =  $doi->message->publisher ?? '';

      $this->entity->field_doi_title =  $title;
      $this->entity->field_doi_description = $abstract;
      $this->entity->field_doi_authors = $authors;
      $this->entity->field_doi_date_issued = $date_issued;
      $this->entity->field_doi_date_published = $date_published_online;
      $this->entity->field_doi_issue = $issue;
      $this->entity->field_doi_journal_name = $journal_name;
      $this->entity->field_doi_page_range = $page_range;
      $this->entity->field_doi_publisher = $publisher;
      $this->entity->field_doi_volume = $vol;

      $this->entity->save();
    }

  }
  
}
