<?php
namespace Drupal\scitalk_feeds_cern_api_parser\Component;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\scitalk_feeds_cern_api_parser\Component\CERNTalkParser;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

// This class is responsible for fetching talks from the CERN API, creating the corresponding talk nodes in Drupal, and adding subtitle download tasks to the queue.
// It is used by the CERNTalkCommands class to fetch talks based on a date range provided as input.
class CERNTalkFetcher {
    private $cernAPIURL = 'https://videos.cern.ch/api/records/';
    private $feedGroupID;
    private $queueFactory;
    private $cernTalkParser;
    private $client;
    
    public function __construct(QueueFactory $queueFactory, ClientInterface $client) {
        $this->queueFactory = $queueFactory;
        $this->client = $client;

        $source_field = \Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['label' => 'CERN-CDS']);
        $source_field = reset($source_field);
        $this->feedGroupID = $source_field->id() ?? 0;
        $this->cernTalkParser = new CERNTalkParser($this->queueFactory, $this->feedGroupID);
    }

    public function byDateRange($from, $to = "*", $sort = "mostrecent", $page_limit = 100): array {
        // multiple years
        // https://videos.cern.ch/api/records/?q=collections:Lectures AND date:[2022-01-01 TO 2023-12-31]&sort=oldest&size=100

        // date range
        // https://videos.cern.ch/api/records/?q=collections:Lectures AND date:[2022-01-01 TO 2022-12-31]

        $url = $this->cernAPIURL;

        $options = [
            RequestOptions::TIMEOUT => 30,
            RequestOptions::HEADERS => [],
        ];

        $i = 0;
        $page = 1;
        $fetched_count = 0;
        $data = [];
        $next = null;
        do {
            $params = [
                'q' => 'collections=Lectures AND date:['. $from .' TO '. $to .']', // only fetch talks updated in the given date range
                'sort' => $sort,
                'size' => $page_limit,
                'page' => $page,
            ];

            $result = $this->fetchResults($url, $options, $params);
            $talks = $result['talks'];
            $next = $result['next'];
            $talks_total = $result['total'];
            $i++;

            // create the talks as nodes and add the subtitle download task to the queue
            foreach ($talks as $talk) {
                $this->createSciVideoTalks($talk);
            }

            parse_str(parse_url($next, PHP_URL_QUERY), $queryParams);
            $page = $queryParams['page'] ?? ($i + 1);
            $fetched_count += $page_limit;

            $data = array_merge($data, $talks);

        }  while ($next);

        return $data;
        // return json_encode($data);
    }
    
     private function fetchResults($url, $options, $params): array {
        $result = [];
        try {
            $query = [
                'query' => $params
            ];

            $response = $this->client->getAsync($url, $query, $options)->wait();
            // $response = $client->get($url, $query, $options);
            $raw_data = $response->getBody();
            $data = json_decode($raw_data);
            $result = $this->cernTalkParser->parseData($data);
        }
        catch (RequestException $e) {
            $args = ['%site' => $url, '%error' => $e->getMessage()];
            echo "ERROR: " . $e->getMessage() . "\n";

            // throw new FetchException(strtr('The feed from %site seems to be broken because of error "%error".', $args));
        }

        return $result;
    }

    /**
     * Creates a SciVideo Talk node based on the given talk data. 
     * If a node with the same talk number and source repository already exists, it will be returned instead of creating a new one.
     *
     * @param array $talk
     *   The talk data.
     *
     * @return \Drupal\Core\Entity\EntityInterface
     *   The entity found or created.
    */
    private function createSciVideoTalks($talk): EntityInterface {
        $properties = [
            "type" => "talk",
            "uid" => 1,
            "title" => $talk['title'],
            "field_talk_number" => $talk['talk_number'],
            "field_talk_date" => $talk['talk_date'],
            "field_talk_abstract" => [
                "value" => $talk['description'],
                "format" => "full_html"
            ],
            // "field_talk_source_event" => $talk['source_event'],
            // "field_talk_collection" => $talk['collection'],
            // "field_talk_video_url" => $talk['video_url'],
            "field_talk_source_event" => [
                "uri" => $talk['source_event'],
                "title" => $talk['source_event']
            ],
            "field_talk_speakers_text" => $talk['speakers'],
            "field_talk_location" => $talk['location'],
            "field_talk_collection" => array_map(function($collection) {
                    return ["target_id" => $collection];
                }, explode(";", $talk['collection'])) ?? [],
            
            "field_talk_video_url" => [
                "uri" => $talk['video_url'],
                "title" => $talk['title'],
            ],
            "field_talk_source_repository" => [
                "target_id" => $this->feedGroupID
            ],
        ];

        $query_properties = [
            "field_talk_number" => $talk['talk_number'],
            "type" => "talk",
            "field_talk_source_repository" => ["target_id" => $this->feedGroupID],
        ];
        $storage = \Drupal::entityTypeManager()->getStorage('node');
        $entity = $storage->loadByProperties($query_properties) ?? [];
        if (!empty($entity)) {
            echo "Entity with talk number " . $talk['talk_number'] . " already exists, skipping creation. Entity ID: " . reset($entity)->id() . "\n";
            return reset($entity);
        }

        $newTalk = $storage->create($properties);
        $newTalk->save();
        echo "Created new talk: " . $talk['title'] . " with ID: " . $newTalk->id() . "\n";
        return $newTalk;
    }
}