<?php
namespace Drupal\scitalk_base\SciVideosIntegration\Entities;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Exception;

use Drupal\scitalk_base\SciVideosIntegration\Authentication\SciVideosAuthentication;

class EntityBase {
    private $scivideos;
    private $url;

    public function __construct(SciVideosAuthentication $scivideos, $path) {
        $this->scivideos = $scivideos;
        $this->url = $scivideos->getBaseUrl() . $path;
    }

    public function fetchById($scivideo_uuid) {
        $filter = '/' . $scivideo_uuid;
        return $this->fetch($filter);
    }

    public function fetch($filter = '') {
        $url = $this->url . $filter;

        $params = [
            'header'  => "Content-Type: application/vnd.api+json; charset=UTF-8"
        ];

        $client = \Drupal::httpClient($params);

        $response = NULL;
        try {
            $request = $client->get($url);
            $response = $request->getBody();
        }
        catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
            if (!empty($res = $e->getResponse()->getBody()->getContents())) {
                $response = $this->getErrors($res);
            }
            else {
              $response = $e->getMessage();
            }
      
            \Drupal::logger('scitalk_base')->error('SciVideos Fetch: ' . $url . '<br>ERROR -> ' . $response  );
            
            throw new Exception('FETCH '. $url . $filter . '<br>'. $response);

            // if (!empty($res = $e->getResponse()->getBody()->getContents())) {
            //     $err = json_decode($res);
            //     if ($err->errors[0]->status != 404) {//if error is other than not found then log this error
            //         $msg = 'SciVideos Fetch: ' . ( $err->errors[0]->title ?? '');
            //         \Drupal::logger('scitalk_base')->error($msg);
            //     }
            // }
            // else {
            //     \Drupal::logger('scitalk_base')->error('SciVideos Fetch: ' . print_r($e->getMessage() , TRUE));
            // }
        }
        finally {
            return $response;
        }

    }

    public function create($resource) {
        return $this->doSave($resource);
    }

    public function update($resource) {
        $resource_path = '/' . $resource['data']['id'];
        return $this->doSave($resource, 'PATCH', $resource_path);
    }

    public function delete($resource) {
        $resource_path = '/' . $resource['data']['id'];
        return $this->doDelete($resource_path);
    }

    private function doSave($resource, $method = 'POST', $resource_to_patch = '') {
        $body = [
            'json' => $resource 
        ];
    
        //need to create this handler since the default guzzle client is not accepting content-type of application/vnd.api+json that the JSON API requires: it changes it to application/json 
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push($this->addHeader('Authorization', "Bearer {$this->scivideos->getAccessToken()}"));
        $stack->push($this->addHeader('Content-Type', 'application/vnd.api+json'));
        $stack->push($this->addHeader('Accept', 'application/vnd.api+json'));
    
        $client = new \GuzzleHttp\Client(['handler' => $stack]);
        // $client = \Drupal::httpClient(['handler' => $stack]);
    
        $response = '';
        // $url = $this->scivideos->getBaseUrl() . $resource_to_patch;
        $url = $this->url . $resource_to_patch;
        try {
            $request = $client->request($method, $url, $body);	    
            $response = $request->getBody();
        
            if (!empty($msg = $this->getErrors($response))) {
                throw new Exception($msg);
            }
        
            return $response;
    
        }
        catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
            if (!empty($res = $e->getResponse()->getBody()->getContents())) {
                $response = $this->getErrors($res);
            }
            else {
                $response = $e->getMessage();
            }
        
            $response .= '<br> Title:'. $resource['data']['attributes']['title'] ?? '';
            throw new Exception('Save SciVideos'. $response);
        }
    }

    public function doDelete($talk_to_delete, $resource = NULL) {
        $body = [];
        if (!empty($resource)) {
            $body = [
                'json' => $resource
            ];
        }
    
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push($this->addHeader('Authorization', "Bearer {$this->scivideos->getAccessToken()}"));
        $stack->push($this->addHeader('Content-Type', 'application/vnd.api+json'));
        $stack->push($this->addHeader('Accept', 'application/vnd.api+json'));
    
        $client = new \GuzzleHttp\Client(['handler' => $stack]);
    
        $response = '';
        $url = $this->url . $talk_to_delete;
    
        try {
            $request = $client->delete($url, $body);
            $response = $request->getBody();
    
            if (!empty($msg = $this->getErrors($response))) {
                throw new Exception($msg);
            }
    
            return $response;
    
        }
        catch (ClientException | RequestException | ConnectException | GuzzleException | BadResponseException | ServerException $e) {
            if (!empty($res = $e->getResponse()->getBody()->getContents())) {
                $response = $this->getErrors($res);
            }
            else {
                $response = $e->getMessage();
            }
    
            throw new Exception('Delete SciVideos'. $response);
        }
    }

    private function addHeader($header, $value) {
        return function (callable $handler) use ($header, $value) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $header, $value) {
                $request = $request->withHeader($header, $value);
                return $handler($request, $options);
            };
        };
    }

    private function getErrors($response) {
        $msg = '';
        $decoded_response = json_decode($response);
        if (!empty($decoded_response->errors)) {
          $error = current($decoded_response->errors);
          $status =  $error->status;
          $error_msg = $error->title ?? '';
          $error_detail = $error->detail ?? '';
    
          $msg = "ERROR!:  $status $error_msg: $error_detail";
        }
    
        return $msg;
    }
    
}