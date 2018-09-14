<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks Seo recommedations: contrib modules, robots.txt.
 *
 * @AuditPlugin(
 *   id = "seo_recommendations",
 *   label = @Translation("Checks Seo recommedations: contrib modules and robots.txt."),
 *   category = "other",
 *   requirements = {},
 * )
 */
class OtherSEORecommendationsPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Returns the default http client.
   *
   * @var \GuzzleHttp\Client
   *   A guzzle http client instance.
   */
  protected $httpClient;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, Client $client, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->httpClient = $client;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('http_client'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_value = $this->getSettings();
    $form['modules'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Modules to check'),
      '#description' => $this->t('Place one module per line.'),
      '#default_value' => $default_value['modules'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $params = [];
    $modules = $this->parseLines($this->getSettings()['modules']);
    foreach ($modules as $module) {
      if (!$this->moduleHandler->moduleExists($module)) {
        $params['missed_modules'][] = $module;
      }
    }

    $host = $this->request->getSchemeAndHttpHost();
    try {
      $this->httpClient->get($host . '/robots.txt');
    }
    catch (RequestException $e) {
      $params['robots_txt_unavailable'] = TRUE;
    }

    if (!empty($params)) {
      $issues = [];
      foreach ($params['missed_modules'] as $missed_module) {
        $issues[] = [
          '@issue_title' => 'Missed module @module',
          '@module' => $missed_module,
        ];
      }

      if (isset($params['robots_txt_unavailable'])) {
        $issues[] = [
          '@issue_title' => 'Missed file robots.txt',
        ];
      }

      return $this->fail(NULL, ['issues' => $issues]);
    }

    return $this->success();
  }

}
