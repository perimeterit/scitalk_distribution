<?php
namespace Drupal\scitalk_feeds_cern_api_parser\Commands;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Queue\QueueFactory;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drupal\scitalk_feeds_cern_api_parser\Component\CERNTalkFetcher;

/**
 * Drush commands that add items to a queue.
 */
class CERNTalkCommands extends DrushCommands {
    /**
     * Logger service.
     *
     * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
     */
    protected $loggerChannelFactory;

    /**
     * The queue factory service.
     *
     * @var \Drupal\Core\Queue\QueueFactory
     */
    protected $queueFactory;

    protected $queueProcessor;
    protected $time;
    protected $entityTypeManager;

    /**
     * Constructs a new QueueCommands object.
     *
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
     *   Logger service.
     * @param \Drupal\Core\Queue\QueueFactory $queueFactory
     *   The queue factory service.
     */
    public function __construct(ContainerInterface $container, LoggerChannelFactoryInterface $loggerChannelFactory, QueueFactory $queueFactory, TimeInterface $time) {
        $this->queueProcessor = $container->get('scitalk_feeds_cern_api_parser.cern_queue_runner');
        $this->loggerChannelFactory = $loggerChannelFactory;
        $this->queueFactory = $queueFactory;
        $this->time = $time;
        $this->entityTypeManager = $container->get('entity_type.manager');
    }

    /**
     * Command to pull vtt's from CERN for exisiting entities in the queue
     * 
     * @command scitalk_feeds_cern_api_parser:pull-vtts
     * @param array $options
     * @aliases cern:pull-vtts
     * @usage cern:pull-vtts
     */
    public function pullVTTs($options = ['time' => 60]) {
        $queueProcessor = $this->queueProcessor;
        if (!$queueProcessor->queueHasItems('scitalk_feeds_cern_subtitle_downloader_queue') ) {
            $this->logger()->notice("Queue 'scitalk_feeds_cern_subtitle_downloader_queue' is empty.");
            return;
        }

        $proccessed = $queueProcessor->run('scitalk_feeds_cern_subtitle_downloader_queue', $options['time']);
        $this->logger()->notice("Pulled vtts from CERN: " . $proccessed);
    }

    /**
     * Command to fetch Talks from CERN between a date range
     * 
     * @command scitalk_feeds_cern_api_parser:fetch-by-date
     * @param array $options
     * @aliases cern:by-date
     * @usage cern:by-date --from="2022-01-01" --to="2022-12-31" --page_limit=50
     */
    public function fetchByDate($options = ['from' => '', 'to' => '', 'page_limit' => 100]) {
        if (!$this->checkDate($options['from']) || !$this->checkDate($options['to'])) {
            $this->logger()->error("Invalid date format. Please use YYYY-MM-DD.");
            $this->printCommandUsage('scitalk_feeds_cern_api_parser:fetch-by-date');
            return;
        }

        $sort = "mostrecent";
        $fetch = new CERNTalkFetcher($this->queueFactory, \Drupal::httpClient());
        $talks = $fetch->byDateRange($options['from'], $options['to'], $sort, $options['page_limit']);
        $this->logger()->notice("Fetched " . count($talks) . " talks from CERN between " . $options['from'] . " and " . $options['to']);
    }

    /**
     * Command to fetch Talks from CERN between a range of years
     * 
     * @command scitalk_feeds_cern_api_parser:fetch-by-year
     * @param array $options
     * @aliases cern:by-year
     * @usage cern:by-year --from=2022 --to=2023 --page_limit=50
     */
    public function fetchByYear($options = ['from' => '', 'to' => '', 'page_limit' => 100]) {
        $from = $options['from'] . '-01-01';
        $to = $options['to'] . '-12-31';
        if (!$this->checkDate($from) || !$this->checkDate($to)) {
            $this->logger()->error("Invalid date format. Please use YYYY-MM-DD.");
            $this->printCommandUsage('scitalk_feeds_cern_api_parser:fetch-by-year');
            return;
        }

        $sort = "oldest";
        $fetch = new CERNTalkFetcher($this->queueFactory, \Drupal::httpClient());
        $talks = $fetch->byDateRange($from, $to, $sort, $options['page_limit']);
        $this->logger()->notice("Fetched " . count($talks) . " talks from CERN between " . $options['from'] . " and " . $options['to']);
    }

    /**
     * Command to return the number of items in the queue
     *
     * @command scitalk_feeds_cern_api_parser:items
     * @aliases cern:items
     * @usage cern:items
     */
    public function itemsInQueue() {
        $queueProcessor = $this->queueProcessor;
        $numItems = $queueProcessor->queueItems('scitalk_feeds_cern_subtitle_downloader_queue');
        $this->logger()->notice("Number of items in the queue: " . $numItems);
    }

    /* Check if the date is in the correct format */
    private function checkDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /* Print the usage of a command */
    private function printCommandUsage($command) {
        $self = Drush::aliasManager()->getSelf();
        $process = $this->processManager()->drush($self, 'help', [$command]);
        $process->run();
        $this->output()->writeln($process->getOutput());
    }
}