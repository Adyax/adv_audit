<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class implementation.
 *
 * @AdvAuditCheck(
 *  id = "page_speed_insights",
 *  label = @Translation("Page speed insights"),
 *  category = "performance",
 *  severity = "low",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class PageSpeedInsightsCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

  /**
   * Acceptable Insights score.
   */
  const TARGET_SCORE = 85;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * Constructs a new CronSettingsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory object.
   * @param \GuzzleHttp\Client $http_cient
   *   Guzzle http client.
   * @param \Drupal\Core\State\StateInterface $state
   *   Defines the interface for the state system.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, Client $http_cient, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->httpClient = $http_cient;
    $this->state = $state;
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
      $container->get('http_client'),
      $container->get('state')
    );
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.config.gi_key';
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigScore() {
    return 'adv_audit.plugin.' . $this->id() . '.config.gi_target_score';
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $gi_link = Link::fromTextAndUrl('Link', Url::fromUri('https://developers.google.com/speed/pagespeed/insights'));
    $status = AuditResultResponseInterface::RESULT_PASS;
    $key = $this->state->get($this->buildStateConfigKey());
    $target_score = $this->state->get($this->buildStateConfigScore());

    // Check plugin settings.
    if (empty($key) || empty($target_score)) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, $this->t('Please check plugin settings and provide API key and target score.'));
    }

    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    // Build request URL.
    $options = ['absolute' => TRUE, 'query' => ['url' => $url, 'key' => $key]];
    $gi_url = Url::fromUri('https://www.googleapis.com/pagespeedonline/v4/runPagespeed', $options)->toString();

    foreach (['desktop', 'mobile'] as $strategy) {
      // Get insights result.
      $response = NULL;
      try {
        $response = $this->httpClient->get($gi_url . '&strategy=' . $strategy);
        $response = $response->getBody();
        $response = json_decode($response);
      }
      catch (RequestException $e) {
        watchdog_exception('adv_auditor', $e);
        return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, $this->t('Request failed. Please check your logs.'));
      }

      $score[] = ucfirst($strategy) . ': ' . $response->ruleGroups->SPEED->score;

      // Mark the whole run as failed if any of tests didn't pass.
      if ($response->ruleGroups->SPEED->score < $target_score) {
        $status = AuditResultResponseInterface::RESULT_FAIL;
      }

      // Build suggestions list.
      foreach ($response->formattedResults->ruleResults as $data) {
        if (!empty($data->ruleImpact) && $data->ruleImpact > 0) {
          $optimization_suggestions[] = [
            'strategy' => $strategy,
            'rule_name' => $data->localizedRuleName,
          ];
        }
      }
    }

    $score[] = $this->t('Here you can run and see extended results %gi_link', ['%gi_link' => $gi_link->toString()]);

    $arguments = [
      '%items' => $optimization_suggestions,
      '%score' => $score,
    ];
    return new AuditReason($this->id(), $status, NULL, $arguments);

  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {
    $link = Link::fromTextAndUrl('Link', Url::fromUri('https://developers.google.com/speed/docs/insights/v4/first-app'));
    $form['gi_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter you API key.'),
      '#default_value' => $this->state->get($this->buildStateConfigKey()),
      '#description' => $this->t('You can create API key via this @link', ['@link' => $link->toString()]),
    ];

    $form['gi_target_score'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter desired target score.'),
      '#default_value' => $this->state->get($this->buildStateConfigScore()),
      '#description' => $this->t('Here you can change target score for your tests. [1-100]'),
      '#attributes' => [
        'min' => '1',
        'max' => '100',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    // Get value from form_state object and save it.
    $values = ['additional_settings', 'plugin_config', 'gi_key'];
    $value = $form_state->getValue($values, 0);
    $this->state->set($this->buildStateConfigKey(), $value);

    $scores_conf = ['additional_settings', 'plugin_config', 'gi_target_score'];
    $score_conf = $form_state->getValue($scores_conf, self::TARGET_SCORE);
    $this->state->set($this->buildStateConfigScore(), $score_conf);
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    $build = [];

    // Override output only if we have correct GI response.
    if (in_array($type, [AuditMessagesStorageInterface::MSG_TYPE_FAIL, AuditMessagesStorageInterface::MSG_TYPE_SUCCESS])
      && !empty($args = $reason->getArguments()) && !empty($args['%score'])) {

      $build['google_insights_score'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('PageSpeed Insights Score:'),
        '#list_type' => 'ul',
        '#items' => $args['%score'],
      ];

      $build['google_insights_result'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Version'),
          $this->t('Description'),
        ],
        '#rows' => $args['%items'],
        '#empty' => $this->t('No actions needed.'),
      ];

      return $build;
    }

    return [];
  }

}
