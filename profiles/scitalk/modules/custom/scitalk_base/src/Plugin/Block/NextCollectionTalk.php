<?php

namespace Drupal\scitalk_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
//use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
 use Drupal\Core\Entity\EntityTypeManagerInterface;
//use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a 'Next Talk within a Collection' Block.
 *
 * @Block(
 *   id = "next_talk_in_collection_block",
 *   admin_label = @Translation("Next Talk withing a Collection Block"),
 *   category = @Translation("Next Talk withing a Collection"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class NextCollectionTalk extends BlockBase implements ContainerFactoryPluginInterface { //BlockPluginInterface {

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
   * Constructs a new NextCollectionTalk object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
    *   The entity type manager.
   */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager')
        );
    }


    /**
     * {@inheritdoc}
     */
    public function build() {
        $build = [];
        $talk = $this->getContextValue('node');
        if ($talk instanceof NodeInterface) {
            $collectionTalksStats = \Drupal::service('scitalk_base.talks_in_collection_stats');
            $next_talk = $collectionTalksStats->fetchNextTalkinCollecton($talk);

            if (!empty($next_talk)) {
                $next_talk_id = $next_talk->nid->value ?? 0;
                $view_mode = 'horizontal_card';
                $node = $this->entityTypeManager->getStorage('node')->load($next_talk_id);
                if ($node) {
                    $view_builder = $this->entityTypeManager->getViewBuilder('node');
                    // $build['content'] = $view_builder->view($node, $view_mode);
                    $build = [
                        '#title' => 'Next talk',
                        'content' => $view_builder->view($node, $view_mode)                        
                    ] ;
                }
            }
        }
        return $build;
    }

    /**
     * {@inheritdoc}
     */
    protected function blockAccess(AccountInterface $account) {
        return AccessResult::allowedIfHasPermission($account, 'access content');
    }

  }
