<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Authentication;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use DateTime;
use DateInterval;

class SciVideosAuthentication {
    const TOKEN_PATH = 'oauth/token';
    const PRIVATE_STORAGE = 'scivideos_access_token';

    private static $instance = NULL;
    private $base_url = '';
    private $credentials_data = [];
    private $private_store;

    private function __construct(PrivateTempStoreFactory $private_store) {
        $this->private_store = $private_store;

        $config = \Drupal::config('scitalk_base.settings');

        $this->base_url =  $config->get('scivideos_api_url') ?? '';

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

    public static function getInstance(PrivateTempStoreFactory $private_store): SciVideosAuthentication {
        if (self::$instance == NULL || self::$instance->tokenExpired()) {
            self::$instance = new SciVideosAuthentication($private_store);
        }
        return self::$instance;
    }
 
    public function getBaseUrl() {
        return $this->base_url;
    }

    public function getAccessToken() {
        if ($this->tokenExpired()) {
            $this->private_store?->get('scitalk_base')?->delete(SciVideosAuthentication::PRIVATE_STORAGE);
            return $this->renewToken();
        }

        $access_token = $this->private_store?->get('scitalk_base')?->get(SciVideosAuthentication::PRIVATE_STORAGE)['token'];
        return $access_token;
    }

    private function tokenExpired() {
        $now = new DateTime();
        $tempstore = $this->private_store->get('scitalk_base');
        if ($storage = $tempstore->get(SciVideosAuthentication::PRIVATE_STORAGE)) {
            $expires_at = $storage['expires'];
            return $expires_at <= $now;
        }
        return TRUE;
    }

    private function renewToken() {
        $tempstore = $this->private_store->get('scitalk_base');
        if ($storage = $tempstore->get(SciVideosAuthentication::PRIVATE_STORAGE)) {
            $access_token = $storage['token'];
            return $access_token;
        }

        $response = $this->authenticate();
        $access_token = $response->access_token;

        $expires_in = (int)$response->expires_in;
        $expires_in -= 15; //i will expire it 15 secs before
        $expires_at = $this->setExpiresOn($expires_in);

        $tempstore->set(SciVideosAuthentication::PRIVATE_STORAGE, ['token' => $access_token, 'expires' => $expires_at]);
        return $access_token;
    }

    private function setExpiresOn($expires_in = 0) {
        $expires_at = new DateTime();
        $expires_in = $expires_in < 0 ? 0 : $expires_in;
        $expires_at->add(new DateInterval('PT'.$expires_in.'S'));
        return $expires_at;
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