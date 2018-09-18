<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Constructs a new PerformancePageCache object.
 *
 * @AuditPlugin(
 *  id = "page_caching_performance",
 *  label = @Translation("Page caching performance"),
 *  category = "performance",
 *  requirements = {},
 * )
 */
class PerformancePageCachePlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  /**
   * Returns the default http client.
   *
   * @var \GuzzleHttp\Client
   *   A guzzle http client instance.
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new PerformancePageCache object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Defines the interface for the state system.
   * @param \GuzzleHttp\Client $http_cient
   *   Guzzle http client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, Client $http_cient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->httpClient = $http_cient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    // Check Drupal settings.
    $cache_config = $this->configFactory->get('system.performance');
    $cache_lifetime = $cache_config->get('cache.page');

    // Check varnish response headers.
    $host = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(TRUE)->getGeneratedUrl();
    $response = $this->httpClient->get($host);
    $response_header = $response->getHeader('X-Cache');
    $response_header = !empty($response_header) ? reset($response_header) : $response_header;
    $varnish_hits = $response->getHeader('X-Cache-Hits');
    $varnish_hits = !empty($varnish_hits) ? reset($varnish_hits) : $varnish_hits;

    // Check varnish hits.
    if (((!empty($response_header) && strpos($response_header, 'HIT') !== FALSE)
      || (!empty($varnish_hits) && $varnish_hits > 0))
      && $cache_lifetime['max_age'] > 0) {
      return $this->success();
    }

    return $this->fail(NULL, [
      'issues' => [
        'page_caching_performance' => [
          '@issue_title' => 'Need to check and fix cache settings.'
        ],
      ],
    ]);
  }

}
