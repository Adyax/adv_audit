<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
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
class PageSpeedInsightsCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

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
    $target_score = $this->state->get($this->buildStateConfigScore());

    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    // Build request URL.
    $options = ['absolute' => TRUE, 'query' => ['url' => $url]];
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
        return $this->fail(NULL, [
          'issues' => [
            'page_speed_insights_no_response' => [
              '@issue_title' => 'Request failed. Please check your logs.',
            ],
          ],
        ]);
      }

      $score[] = ucfirst($strategy) . ': ' . $response->ruleGroups->SPEED->score;

      // Mark the whole run as failed if any of tests didn't pass.

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

    if ($response->ruleGroups->SPEED->score < $target_score) {
      $issues = $this->getIssues($arguments);

      return $this->fail(NULL, [
        'issues' => $issues,
        '%link' => $arguments['%score'][2],
      ]);
    }
    return $this->success($arguments);

  }

  /**
   * Get issues.
   *
   * @param array $arguments
   *   Array with parameters for issues.
   *
   * @return array
   *   Issues.
   */
  private function getIssues(array $arguments) {
    $issues = [];

    foreach ($arguments['%items'] as $item) {
      if ($item['strategy'] === 'desktop') {
        $issues[] = [
          '@issue_title' => 'Issue for Desktop devices: @rule_name',
          '@rule_name' => $item['rule_name'],
        ];
      }
      elseif ($item['strategy'] === 'mobile') {
        $issues[] = [
          '@issue_title' => 'Issue for Mobile devices: @rule_name',
          '@rule_name' => $item['rule_name'],
        ];
      }
    }
    $issues['score_0'] = [
      '@issue_title' => 'Score for @score_0',
      '@score_0' => $arguments['%score'][0],
    ];
    $issues['score_1'] = [
      '@issue_title' => 'Score for @score_1',
      '@score_1' => $arguments['%score'][1],
    ];

    return $issues;
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
  public function configFormSubmit(array $form, FormStateInterface $form_state) {
    // Get value from form_state object and save it.
    $values = ['additional_settings', 'plugin_config', 'gi_key'];
    $value = $form_state->getValue($values, 0);
    $this->state->set($this->buildStateConfigKey(), $value);

    $scores_conf = ['additional_settings', 'plugin_config', 'gi_target_score'];
    $score_conf = $form_state->getValue($scores_conf, self::TARGET_SCORE);
    $this->state->set($this->buildStateConfigScore(), $score_conf);
  }

}
