<?php

namespace Drupal\scitalk_feeds_cern_api_parser\Feeds\Fetcher;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds\File\FeedsFileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Queue\QueueFactory;
use Drupal\scitalk_feeds_cern_api_parser\Component\CERNTalkParser;

/**
 * Constructs FeedsCERNApiFetcher object.
 *
 * @FeedsFetcher(
 *   id = "scitalk_feeds_cern_api_parser_fetcher",
 *   title = @Translation("CERN API"),
 *   description = @Translation("Fetch data from a CERN API"),
 *   form = {
 *     "configuration" = "Drupal\scitalk_feeds_cern_api_parser\Feeds\Fetcher\Form\FeedsCERNApiFetcherForm",
 *     "feed" = "Drupal\scitalk_feeds_cern_api_parser\Feeds\Fetcher\Form\FeedsCERNApiFetcherFeedForm",
 *   }
 * )
 */
class FeedsCERNApiFetcher extends PluginBase implements ClearableInterface, FetcherInterface, ContainerFactoryPluginInterface {

    /**
     * The maximum number of talks to import.
     *
     * @var int
     */
    protected $talksLimit;

    /**
     * The pagination limit for the API requests.
     *
     * @var int
     */
    protected $pageLimit;

    protected $requestTimeout;

    /**
     * The url to CERN videos.
     *
     * @var string
     */
    protected $cernVideoURL;

    /**
     * The group ID for CERN.
     *
     * @var int
     */
    protected $feedGroupID;

    /**
     * The Guzzle client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * Drupal file system helper.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected $fileSystem;

    /**
     * Drupal file system helper for Feeds.
     *
     * @var \Drupal\feeds\File\FeedsFileSystemInterface
     */
    protected $feedsFileSystem;

    protected $queueFactory;
    protected $cernTalkParser;

    public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, FileSystemInterface $file_system, FeedsFileSystemInterface $feeds_file_system, QueueFactory $queueFactory) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->client = $client;
        $this->fileSystem = $file_system;
        $this->feedsFileSystem = $feeds_file_system;
        $this->queueFactory = $queueFactory;

        $this->talksLimit = (int) $this->getConfiguration('import_talks_limit');
        $this->pageLimit = (int) $this->getConfiguration('results_per_page');
        $this->requestTimeout = (int) $this->getConfiguration('request_timeout');
        $this->cernVideoURL = 'https://videos.cern.ch/record/';
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('http_client'),
            $container->get('file_system'),
            $container->get('feeds.file_system.in_progress'),
            $container->get('queue'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(FeedInterface $feed, StateInterface $state): RawFetcherResult {
        $sink = $this->feedsFileSystem->tempnam($feed, 'http_fetcher_');
        $this->feedGroupID = $feed->field_feeds_group->target_id ?? 0; // grab the group ID for this feed
        $this->cernTalkParser = new CERNTalkParser($this->queueFactory, $this->feedGroupID);

        $response = $this->get($feed->getSource(), $sink, [], $feed->getConfigurationFor($this) ?: []);
       
        if ($response !== FALSE) {
            return new RawFetcherResult($response);
        }
        else {
            return new RawFetcherResult('');
        }
    }

    /**
     * Performs a GET request.
     *
     * @param string $url
     *   The URL to GET.
     * @param string $sink
     *   The location where the downloaded content will be saved. This can be a
     *   resource, path or a StreamInterface object.
     * @param array $options
     *   (optional) Additional options to pass to the request.
     *   See https://docs.guzzlephp.org/en/stable/request-options.html.
     * @param array $feed_configuration
     *   (optional) Feed entity configuration.
     *
     * @return string
     *   A json encoded string of data.
     *
     * @throws \Drupal\feeds\Exception\FetchException
     *   Thrown if the GET request failed.
     *
     * @see \GuzzleHttp\RequestOptions
     */
    protected function get($url, $sink, array $options = [], array $feed_configuration = []) {
        $url = Feed::translateSchemes($url);

        $options += [
            RequestOptions::SINK => $sink,
            RequestOptions::TIMEOUT => $this->configuration['request_timeout'],
            RequestOptions::HEADERS => [],
        ];

        $headers = [];

        // https://videos.cern.ch/api/records/?sort=mostrecent&q=collections%3DLectures
        // https://videos.cern.ch/api/records/?q=collections:Lectures AND _updated:[2026-02-09 TO *]&sort=mostrecent
        // https://videos.cern.ch/api/records/?q=collections%3ALectures%20AND%20_updated%3A%5B2026-02-17%20TO%20*%5D%26sort%3Dmostrecent%26size%3D100
        
        // latest talks:
        // https://videos.cern.ch/api/records/?sort=mostrecent&q=collections=Lectures AND _updated:[2026-02-17 TO *]

        // multiple years
        // https://videos.cern.ch/api/records/?q=collections:Lectures AND date:[2022-01-01 TO 2023-12-31]&sort=oldest&size=100

        // date range
        // https://videos.cern.ch/api/records/?q=collections:Lectures AND date:[2022-01-01 TO 2022-12-31]

        // Add header options.
        $options[RequestOptions::HEADERS] += $headers;
dsm($this->configuration);
        $requests_number = ceil($this->talksLimit / $this->pageLimit);
        $i = 0;
        $page = 1;
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $data = [];
        $fetched_count = 0;
        do {
            // the API doesn't support pagination with a simple offset and limit, but instead with a page number and page size, 
            // so calculate the page number based on the number of talks already fetched and the page limit, 
            // and calculate the page size for the next request based on the remaining talks to fetch and the page limit:

            // No, the API does not like it when the page size is different from one request to another, it has to be the same size 
            // $fetch_size = min($this->pageLimit, ($this->talksLimit - $fetched_count));
            // $params = [
            //     'size' => $fetch_size,
            //     'page' => $i + 1
            // ];

            // $params = [
            //     'size' => $this->pageLimit,
            //     'page' => $page,
            // ];

            $params = [
                'q' => 'collections=Lectures AND _updated:['. $yesterday .' TO *]', // only fetch talks updated since yesterday
                'sort' => "mostrecent",
                'size' => $this->pageLimit,
                'page' => $page,
            ];

            dsm($params);
            $result = $this->fetchResults($url, $sink,  $options, $params);
            $talks = $result['talks'];
            $next = $result['next'];
            $talks_total = $result['total'];
            $i++;
            dsm($result);

            parse_str(parse_url($next, PHP_URL_QUERY), $queryParams);
            $page = $queryParams['page'] ?? ($i + 1);
            $fetched_count += $this->pageLimit;

            $data = array_merge($data, $talks);

        }  while ($i < $requests_number && $next);

        return json_encode($data);
    }

    private function fetchResults($url, $sink, $options, $params): array {
        try {
            $query = [
                'query' => $params
            ];

            $response = $this->client->getAsync($url, $query, $options)->wait();
            $raw_data = $response->getBody();
            $data = json_decode($raw_data);
            // $result = $this->parseData($data);
            $result = $this->cernTalkParser->parseData($data);
        }
        catch (RequestException $e) {
            $args = ['%site' => $url, '%error' => $e->getMessage()];

            // Since the fetch is getting aborted, delete the downloaded file.
            $this->fileSystem->unlink($sink);

            throw new FetchException(strtr('The feed from %site seems to be broken because of error "%error".', $args));
        }

        return $result;
    }


    /**
     * {@inheritdoc}
     */
    public function clear(FeedInterface $feed, StateInterface $state) {
        $this->onFeedDeleteMultiple([$feed]);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        return [
            'import_talks_limit' => 50,
            'results_per_page' => 50,
            'request_timeout' => 30,
        ];
    }
}