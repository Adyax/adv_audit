<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class implementation.
 *
 * @AuditPlugin(
 *  id = "page_speed_insights",
 *  label = @Translation("Page speed insights"),
 *  category = "performance",
 *  requirements = {},
 * )
 */
class PerformancePageSpeedInsightsPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

  /**
   * Acceptable Insights score.
   */
  const TARGET_SCORE = 85;


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
   * Constructs a new PerformancePageSpeedInsights object.
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

    $settings = $this->getSettings();
    $gi_link = Link::fromTextAndUrl('Link', Url::fromUri('https://developers.google.com/speed/pagespeed/insights'));
    $target_score = $settings['gi_target_score'];
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    // Build request URL.
    $options = ['absolute' => TRUE, 'query' => ['url' => $url]];
    $gi_url = Url::fromUri('https://www.googleapis.com/pagespeedonline/v4/runPagespeed', $options)
      ->toString();

    $optimization_suggestions = [];
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
    return $this->success();

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSettings();
    $form['gi_target_score'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter desired target score.'),
      '#default_value' => $settings['gi_target_score'],
      '#description' => $this->t('Here you can change target score for your tests. [1-100]'),
      '#attributes' => [
        'min' => '1',
        'max' => '100',
      ],
    ];

    return $form;
  }

}
