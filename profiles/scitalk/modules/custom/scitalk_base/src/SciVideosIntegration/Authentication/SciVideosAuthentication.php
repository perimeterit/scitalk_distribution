<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Authentication;

use DateTime;
use DateInterval;

class SciVideosAuthentication {
    const TOKEN_PATH = 'oauth/token';

    private static $instance = NULL;
    private $base_url = '';
    private $credentials_data = [];
    private $access_token = '';
    private $expires_in = 0;
    private $expires_at = NULL;

    private function __construct() {
        $config = \Drupal::config('scitalk_base.settings');

        $this->base_url =  $config->get('scivideos_api_url');

        if (substr($this->base_url, -1) != '/') {
            $this->base_url .= '/';
        }

        $this->credentials_data = [
            'grant_type' => 'client_credentials',
            'client_id' => $config->get('scivideos_api_client_id'),
            'client_secret' => $config->get('scivideos_api_client_secret'),
            'scope' => $config->get('scivideos_api_client_scope')
        ];

    }

    public static function getInstance(): SciVideosAuthentication {
        if (self::$instance == NULL || self::$instance->tokenExpired()) {
            self::$instance = new SciVideosAuthentication();
        }
        return self::$instance;
    }

//   private function __construct($base_url = '', $credentials_grant = '') {
//     $this->base_url = $base_url;
//     $this->credentials_data = $credentials_grant;
//     $this->expires_at = new DateTime();

//     // $this->init($base_url, $credentials_grant);
//   }

//   public static function getInstance($base_url = '', $credentials_grant = '') {
//     if (self::$instance == NULL || self::$instance->tokenExpired()) {
//       self::$instance = new SciVideosAuthentication($base_url, $credentials_grant);
//     }
//     return self::$instance;
//   }

//   public function init($base_url, $credentials_grant) {
//     $this->base_url = $base_url;
//     $this->credentials_data = $credentials_grant;
//     $this->expires_at = new DateTime();
//   }
 
    public function getBaseUrl() {
        return $this->base_url;
    }

    public function getAccessToken() {
        if ($this->tokenExpired()) {
            return $this->renewToken();
        }
        return $this->access_token;
    }

    private function tokenExpired() {
        $now = new DateTime();
        return $this->expires_at <= $now;
    }

    private function renewToken() {
        $response = $this->authenticate();
        $this->access_token = $response->access_token;
        $this->expires_in = (int)$response->expires_in;
        $this->expires_in -= 15; //i will expire it 15 secs before just in case
        $this->setExpiresOn();

        return $this->access_token;
    }

    private function setExpiresOn() {
        $now = new DateTime();
        $expires_at = new DateTime();
        $expires_in = $this->expires_in ?? 0;
        $expires_in = $expires_in < 0 ? 0 : $expires_in;
        $expires_at->add(new DateInterval('PT'.$expires_in.'S'));
        $this->expires_at = $expires_at; 
    }

    private function authenticate() {  
        $url = $this->base_url . SciVideosAuthentication::TOKEN_PATH;

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                            "Accept: application/vnd.api+json\r\n",
                'method'  => 'POST',
                'content' => http_build_query($this->credentials_data)
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);
        return $response;
    }     
}