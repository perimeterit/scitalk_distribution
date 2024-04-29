<?php

namespace Drupal\scitalk_stats\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a 'SciTalks Content Stats' Block.
 *
 * @Block(
 *   id = "scitalk_stats_block",
 *   admin_label = @Translation("SciTalk Content Stats Block"),
 *   category = @Translation("SciTalk Content Stats"),
 * )
 */
class SciTalkStats extends BlockBase implements ContainerFactoryPluginInterface { 

    protected $request;
    protected $route;

    /**
   * Constructs a new SciTalkStats object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('current_user')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build() {
        $title = 'Site Stats';
        $collections_stats = $this->getCollectionsStatsByRepository();
        $talks_stats = $this->getTalksStatsByRepository();

        $total_number_of_talks = $this->countTalks() ?? 0;
        $total_number_of_repos = $this->countRepositories() ?? 0;
        $total_number_of_collections = $this->countCollections() ?? 0; 

        $talks = ['total' => $total_number_of_talks, 'stats' => $talks_stats];
        $collections = ['total' => $total_number_of_collections, 'stats' => $collections_stats];

        return [
            '#theme' => 'scitalk_stats_block',
            '#cache' => ['contexts' => ['url.path']],
            '#title' => $title,
            '#repositories' => $total_number_of_repos, 
            '#collections' => $collections,
            '#talks' => $talks
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function blockAccess(AccountInterface $account) {
        $is_admin = in_array('administrator', $account->getRoles());
        return AccessResult::allowedIf($is_admin);
    }

    private function countRepositories() {
        $query_count = \Drupal::entityQuery('node')->condition('type', 'source_repository')->accessCheck(TRUE);
        return $query_count->count()->execute();
    }

    private function countTalks() {
        $query_count = \Drupal::entityQuery('node')->condition('type', 'talk')->accessCheck(TRUE);
        return $query_count->count()->execute();
    }

    private function countCollections() {
        $query_count = \Drupal::entityQuery('taxonomy_term')->condition('vid', 'collection')->accessCheck(TRUE);
        return $query_count->count()->execute();

        // $query_count->count();
        // return $query_count->execute();
    }

    private function getTalksStatsByRepository() {
        // $sql = 'SELECT count(*) as cnt, field_repo_institution_value as repo
        //   FROM node__field_talk_source 
        //   INNER JOIN node__field_repo_institution inst on field_talk_source_target_id = inst.entity_id
        //   group by field_repo_institution_value';

        $sql = "SELECT count(*) as cnt, 
            CASE WHEN inst.field_repo_institution_value is NULL THEN '-No repository source-' ELSE inst.field_repo_institution_value END AS repo
          FROM node
          LEFT JOIN node__field_talk_source talk ON node.nid = talk.entity_id
          LEFT JOIN node__field_repo_institution inst ON talk.field_talk_source_target_id = inst.entity_id
          WHERE node.type = 'talk'
          GROUP BY inst.field_repo_institution_value
          ORDER BY inst.field_repo_institution_value";

        $database = \Drupal::database();
        $query = $database->query($sql);
        $result = $query->fetchAll();
        $data = [];
        foreach ($result as $repo) {
            $data[] = ['institution_name' => $repo->repo, 'number_of_talks' => $repo->cnt];
        }

       return $data;
    }

    private function getCollectionsStatsByRepository() {

        // $sql = "SELECT count(*) as cnt, inst.field_repo_institution_value as repo
        // FROM taxonomy_term_data tx
        // left join taxonomy_term__field_collection_source src on tx.tid = src.entity_id
        // LEFT JOIN node__field_repo_institution inst on src.field_collection_source_target_id = inst.entity_id  
        // where vid='collection'
        // group by src.field_collection_source_target_id, inst.field_repo_institution_value";

        $sql = "SELECT count(*) as cnt, 
              CASE WHEN inst.field_repo_institution_value is NULL THEN '-No repository source-' ELSE inst.field_repo_institution_value END AS repo
          FROM taxonomy_term_data tx
          LEFT JOIN taxonomy_term__field_collection_source src ON tx.tid = src.entity_id
          LEFT JOIN node__field_repo_institution inst ON src.field_collection_source_target_id = inst.entity_id  
          WHERE vid = 'collection'
          GROUP BY src.field_collection_source_target_id, inst.field_repo_institution_value
          ORDER BY inst.field_repo_institution_value";

        $database = \Drupal::database();
        $query = $database->query($sql);
        $result = $query->fetchAll();
        $data = [];
        foreach ($result as $coll) {
            $data[] = ['institution_name' => $coll->repo, 'number_of_collections' => $coll->cnt];
        }
 
       return $data;
    }

  }
