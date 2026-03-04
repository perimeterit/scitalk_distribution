<?php

namespace Drupal\scitalk_feeds_cern_api_parser\Feeds\Fetcher;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\File\FeedsFileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\StreamInterface;
use Generator;
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
    private const int CHUNK_SIZE = 5000;//8192;

    public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, FileSystemInterface $file_system, FeedsFileSystemInterface $feeds_file_system, QueueFactory $queueFactory) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->client = $client;
        $this->fileSystem = $file_system;
        $this->feedsFileSystem = $feeds_file_system;
        $this->queueFactory = $queueFactory;

        $this->readConfiguration();
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

    private function readConfiguration() {
        $this->talksLimit = (int) $this->getConfiguration('import_talks_limit');
        $this->pageLimit = (int) $this->getConfiguration('results_per_page');
        $this->requestTimeout = (int) $this->getConfiguration('request_timeout');
    }
    /**
     * {@inheritdoc}
     */
    public function fetch(FeedInterface $feed, StateInterface $state): RawFetcherResult {
        $sink = $this->feedsFileSystem->tempnam($feed, 'http_fetcher_');
        $this->feedGroupID = $feed->field_feeds_group->target_id ?? 0; // grab the group ID for this feed
        $this->cernTalkParser = new CERNTalkParser($this->queueFactory, $this->feedGroupID);

        // re-read the configuration in case it has been updated from a drush command e.g. new date range for fetching talks, or new talks limit, etc.
        $this->readConfiguration();
        $response = $this->get($feed->getSource(), $sink, [], $this->getConfiguration() ?: []);
       
        if (!empty($response)) {
            return new RawFetcherResult($response);
        }
        return new RawFetcherResult('');
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

        // Add header options.
        $options[RequestOptions::HEADERS] += $headers;

        // if $requests_number is 0, it means there is no limit, so we should keep fetching until there are no more talks to fetch, 
        // which is when the API stops returning a next page link in the response. 
        // So we can set the condition to stop fetching when there are no more talks to fetch or when we have fetched the number of talks specified in the configuration:
        $requests_number = $this->talksLimit > 0 ? ceil($this->talksLimit / $this->pageLimit) : 0;

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $fetch_more = true;
        $fetch_cnt = 0;
        $page = 1;
        $data = [];
        // $fetched_count = 0;

        $query = $feed_configuration['query_pre'] ?? 'collections=Lectures AND _updated:';
        $from = $feed_configuration['from'] ?: $yesterday;
        $to = $feed_configuration['to'] ?: '*';
        $query .= '[' . $from . ' TO ' . $to . ']';
        do {
            // the API doesn't support pagination with a simple offset and limit, but instead with a page number and page size, 
            // so calculate the page number based on the number of talks already fetched and the page limit, 
            // and calculate the page size for the next request based on the remaining talks to fetch and the page limit:

            // No, the API does not like it when the page size is different from one request to another, it has to be the same size 
            // $fetch_size = min($this->pageLimit, ($this->talksLimit - $fetched_count));
            // $params = [
            //     'size' => $fetch_size,
            //     'page' => $fetch_cnt + 1
            // ];

            // $params = [
            //     'size' => $this->pageLimit,
            //     'page' => $page,
            // ];

            $params = [
                // 'q' => 'collections=Lectures AND _updated:['. $yesterday .' TO *]', // only fetch talks updated since yesterday
                'q' => $query,
                'sort' => "mostrecent",
                'size' => $this->pageLimit,
                'page' => $page,
            ];

            // $result = $this->fetchResults($url, $sink,  $options, $params);
            $result = $this->fetchResultsAsStream($url, $sink,  $options, $params);
            $talks = $result['talks'];
            $next = $result['next'] ?? '';
            // $talks_total = $result['total'];
            $fetch_cnt++;

            parse_str(parse_url($next, PHP_URL_QUERY) ?? "", $queryParams);
            $page = $queryParams['page'] ?? ($fetch_cnt + 1);
            // $fetched_count += $this->pageLimit;

            $data = array_merge($data, $talks);
            $fetch_more = $requests_number > 0 ? $fetch_cnt < $requests_number && !empty($next) : !empty($next);
        }  while ($fetch_more);

        return json_encode($data);
    }

    // This function is used to fetch results from the CERN API without using streaming, 
    // which means that it will wait for the entire response to be downloaded before processing it.
    private function fetchResults($url, $sink, $options, $params): array {
        try {
            $query = [
                'query' => $params
            ];

            $response = $this->client->getAsync($url, $query, $options)->wait();
            $raw_data = $response->getBody();
            $data = json_decode($raw_data);
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

    // This function is used to fetch results from the CERN API using a streaming approach,
    // which allows us to process the data as it is being downloaded, instead of waiting for the entire response to be downloaded before processing it.
    // This is especially useful when dealing with large responses, as it can help reduce memory usage and improve performance.
    private function fetchResultsAsStream($url, $sink, $options, $params): array {
        $result = [];
        try {
            $options[RequestOptions::STREAM] = TRUE; // enable streaming

            $query = [
                'query' => $params
            ];

            $response = $this->client->getAsync($url, $query, $options)->wait();
            $raw_data = $response->getBody();
            
            foreach ($this->readData($raw_data) as $data) {
                $result = $this->cernTalkParser->parseData($data);
            }
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
    * Read each line from the stream and yield it as a JSON object
    *
    * @param StreamInterface $stream
    * @return Generator
    */
    public function readData(StreamInterface $stream): Generator  {
        foreach($this->nextLine($stream) as $line) {
            yield json_decode($line);
        }
    }

    /**
     *
     * Read each line from the stream
     * 
     * @param StreamInterface $stream
     * @return Generator
     * */
    private function nextLine(StreamInterface $stream): Generator{
        $buffer = '';

        // read chunks until EOF
        while (!$stream->eof()) {
            $buffer .= $stream->read(self::CHUNK_SIZE);

            // if buffer has new line, yield a JSON line
            while (
                ($pos = strpos($buffer, "\n")) !== false
            ) {
                // get the complete JSON
                $line = substr($buffer, 0, $pos + 1);
                $buffer = substr($buffer, $pos + 1, strlen($buffer));
                yield $line;
            }
        }

        // process the remaining buffer
        if ($buffer !== '') {
            yield $buffer;
        }
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
            // these default values below will be updated if calling from a drush command to pull historical talks
            'from' => '',
            'to' => '',
            'sort' => 'mostrecent',
            'query_pre' => 'collections=Lectures AND _updated:', //default query fetches latest updated talks.
        ];
    }
}