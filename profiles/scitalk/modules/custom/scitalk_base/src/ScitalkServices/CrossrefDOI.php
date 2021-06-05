<?php
namespace Drupal\scitalk_base\ScitalkServices;

use Drupal\Core\Entity\EntityInterface;
use GuzzleHttp\Exception\GuzzleException;  
use GuzzleHttp\Exception\ConnectException;  
use GuzzleHttp\Exception\ClientException;  
use GuzzleHttp\Exception\ServerException;  
use GuzzleHttp\Exception\BadResponseException;  
use GuzzleHttp\Exception\RequestException;  

class CrossrefDOI {

    public function __construct() {
        $config = \Drupal::config('scitalk_base.settings');

        $this->crosref_api_url =  $config->get('crosref_api_url') ?? 'https://api.crossref.org/works/';

        if (substr($this->crosref_api_url, -1) != '/') {
            $this->crosref_api_url .= '/';
        }
    }

    /**
     * Fetch DOI by id
     *
     * @param string doi
     */
    public function getDOI($doi) {
        return $this->fetchDOIByID($doi);
    }

    private function fetchDOIByID($doi_id) {
        $url = $this->crosref_api_url . urlencode($doi_id);

        $client = \Drupal::httpClient();

        $response = '';
        try {
            $request = $client->get($url);
            $response = $request->getBody();
        }
        catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
            if (!empty($res = $e->getResponse()->getBody()->getContents())) {
                $err = json_decode($res);
                $msg = 'Crossref DOI Client error ' . ( $err->errors[0]->title ?? '');
                drupal_set_message(t($msg), 'error');
            }
            
            \Drupal::logger('scitalk_base')->error('<pre>ERROR CONNECTING to Crossref ' . print_r($e->getMessage() , TRUE) .'</pre>');
        }
        finally {
            return $response;
        }
        
    }
}