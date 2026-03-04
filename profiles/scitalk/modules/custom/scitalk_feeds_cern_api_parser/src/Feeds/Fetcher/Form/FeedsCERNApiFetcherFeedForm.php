<?php

namespace Drupal\scitalk_feeds_cern_api_parser\Feeds\Fetcher\Form;


use Drupal\feeds\FeedInterface;
use Drupal\feeds\Utility\Feed;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form on the feed edit page for the FeedsCERNApiFetcher.
 */
class FeedsCERNApiFetcherFeedForm extends ExternalPluginFormBase implements ContainerInjectionInterface {

    /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The Feeds plugin.
   *
   * @var \Drupal\feeds\Plugin\Type\FeedsPluginInterface
   */
  protected $plugin;

  /**
   * Constructs an HttpFeedForm object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin(FeedsPluginInterface $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $form['source'] = [
      '#title' => $this->t('CERN API URL'),
      '#type' => 'url',
      '#default_value' => $feed->getSource(),
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    // $source = $form_state->getValue('source');

    try {
      $url = Feed::translateSchemes($form_state->getValue('source'));
    }
    catch (\InvalidArgumentException $e) {
      $form_state->setError($form['source'], $this->t("The url's scheme is not supported. Supported schemes are: @supported.", [
        '@supported' => implode(', ', Feed::getSupportedSchemes()),
      ]));
      // If the source doesn't have a valid scheme the rest of the validation
      // isn't helpful. Break out early.
      return;
    }
    $form_state->setValue('source', $url);

    try {
      $response = $this->client->get($url);
      $form_state->setValue('source', $url);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      $form_state->setError($form['source'], $this->t('The feed from %site seems to be broken because of error "%error".', $args));

      return;
    }

  
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed->setSource($form_state->getValue('source'));
  }

  /**
   * Performs a GET request.
   *
   * @param string $url
   *   The URL to GET.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the GET request failed.
   */
  protected function get($url) {
    try {
      $response = $this->client->get($url);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The URL from %site seems to be broken because of error "%error".', $args));
    }

    return $response;
  }

}
