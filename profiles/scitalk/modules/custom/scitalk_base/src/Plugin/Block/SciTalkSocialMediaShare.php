<?php

namespace Drupal\scitalk_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
//use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
//use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a 'SciTalks Social Media Share' Block.
 *
 * @Block(
 *   id = "scitalk_social_media_share_block",
 *   admin_label = @Translation("SciTalk Social Media Share Block"),
 *   category = @Translation("SciTalk Social Media Share"),
 * )
 */
class SciTalkSocialMediaShare extends BlockBase implements ContainerFactoryPluginInterface { //BlockPluginInterface {

    protected $request;
    protected $route;

    /**
   * Constructs a new SciTalkSocialMediaShare object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, RequestStack $request_stack, RouteMatchInterface $route) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->currentUser = $current_user;
        $this->request = $request_stack->getCurrentRequest();
        $this->route = $route;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('current_user'),
            $container->get('request_stack'),
            $container->get('current_route_match')
        );
    }


    /**
     * {@inheritdoc}
     */
    public function build() {
        // $current_path = \Drupal::service('path.current')->getPath();
        // $host_url = $this->request->getSchemeAndHttpHost();
        // $alias = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
        // $path = $host_url . '/' . $alias;

        $request = $this->request; //\Drupal::request();
        $route = $this->route->getRouteObject();  //\Drupal::routeMatch()->getRouteObject();
        $title = \Drupal::service('title_resolver')->getTitle($request, $route);

        return [
            '#theme' => 'scitalk_social_media_share_block',
            '#cache' => ['contexts' => ['url.path']],
            '#title' => $title
        ];

    }

    /**
     * {@inheritdoc}
     */
    protected function blockAccess(AccountInterface $account) {
        return AccessResult::allowedIfHasPermission($account, 'access content');
    }

  }
