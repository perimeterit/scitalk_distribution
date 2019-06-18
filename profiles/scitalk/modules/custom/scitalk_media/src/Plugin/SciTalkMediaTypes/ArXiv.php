<?php

namespace Drupal\scitalk_media\Plugin\SciTalkMediaTypes;

use Drupal\scitalk_media\SciTalkMediaPluginBase;


/**
 * SciTalk Arxiv Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this media type.  
 *
 * @Plugin(
 *   id = "SciTalkMediaArXiv",
 *   description = @Translation("The ArXiv plugin for handling various SciTalk Media type functions."),
 *   media_type = "scitalk_arxiv",
 *   media_source = "",
 * )
 */
class ArXiv extends SciTalkMediaPluginBase {
   
  
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'SciTalkMediaArXiv';
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
    //The URI for the API is something like this:  http://export.arxiv.org/api/query?search_query=hep-ex/0307015
    //where the search_query parameter takes the ArXiv citation number as the query.
    $source = $this->entity->bundle->entity->getSource();
    $configuration = $source->getConfiguration();
    $val = $this->entity->{$configuration['source_field']}->getString();
    $uri = 'http://export.arxiv.org/api/query?search_query=' . $val;
    $context = stream_context_create(array(
      'http' => array('timeout' =>10),
    ));
    $atom = @file_get_contents($uri, false, $context);  //@ used to suppress warning.  we don't want warnings!
    //now parse the atom feed
    //we are assuming this is valid atom xml
    
    if($atom) {
      
      $xml = simplexml_load_string($atom);
      $json = json_encode($xml);
      $parsed_values = json_decode($json,TRUE);
      //$parsed_values['entry'] holds our data
      //check to ensure that parsed_values is an array and that it has the entry key
      //id, updated, published, title, summary, author[n...]['name']..., link[][@attributes][title,href, rel, type], category[][@attributes][term, scheme]
      if(array_key_exists('entry', $parsed_values)) {
        //we now try to fill in the arxiv fields on the entity
        $this->entity->field_arxiv_title = $parsed_values['entry']['title'];
        $this->entity->field_arxiv_updated_date = $parsed_values['entry']['updated'];
        $this->entity->field_arxiv_published_date = $parsed_values['entry']['published'];
        $this->entity->field_arxiv_summary = $parsed_values['entry']['summary'];
        //need authors on the Entity?
        $this->entity->save();
      }
      
    }
  }
  
}
