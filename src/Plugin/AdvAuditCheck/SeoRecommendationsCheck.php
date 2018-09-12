<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

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
 * @AdvAuditCheck(
 *   id = "seo_recommendations",
 *   label = @Translation("Checks Seo recommedations: contrib modules and robots.txt."),
 *   category = "other",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class SeoRecommendationsCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface, PluginFormInterface {

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
    $modules = $this->parseLines($default_value = $this->getSettings()['modules']);
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
      return $this->fail('', $params);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type != AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      return [];
    }

    $issue_details = $reason->getArguments();
    if (empty($issue_details)) {
      return [];
    }

    $items = [];

    if (!empty($issue_details['missed_modules'])) {
      $items[] = [
        '#markup' => $this->t('Missed recommended modules:'),
        'children' => [
          '#theme' => 'item_list',
          '#items' => $issue_details['missed_modules'],
        ],
      ];
    }

    if (!empty($issue_details['robots_txt_unavailable'])) {
      $items[] = $this->t('robots.txt file is not available.');
    }
    return [
      '#type' => 'container',
      'msg' => [
        '#markup' => $this->t('There are number of issues.'),
      ],
      'issues' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

}
