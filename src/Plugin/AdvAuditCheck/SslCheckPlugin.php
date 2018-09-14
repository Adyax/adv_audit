<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * SSL test plugin based on SSLLabs API.
 *
 * @AdvAuditCheck(
 *  id = "ssllab_check",
 *  label = @Translation("SSL test"),
 *  category = "security",
 *  severity = "high",
 *  enabled = true,
 * )
 */
class SslCheckPlugin extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

  /**
   * SslLab the main API entry point.
   */
  const SSLLAB_API_URL = 'https://api.ssllabs.com/api/v3/';

  /**
   * SslLab info API call.
   */
  const INFO_API_CALL = 'info';

  /**
   * SslLab analize API call.
   */
  const ANALYZE_API_CALL = 'analyze';

  /**
   * SslLab report URL.
   */
  const REPORT_URL = 'https://www.ssllabs.com/ssltest/analyze.html';

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The audit messages storage service.
   *
   * @var \Drupal\adv_audit\Message\AuditMessagesStorageInterface
   */
  protected $messagesStorage;

  /**
   * The request stack.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, AuditMessagesStorageInterface $messages_storage, Client $http_client, RequestStack $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->messagesStorage = $messages_storage;
    $this->httpClient = $http_client;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('state'),
        $container->get('adv_audit.messages'),
        $container->get('http_client'),
        $container->get('request_stack')
    );
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return array
   *   The generated keys.
   */
  protected function buildStateConfigKeys() {
    return [
      'check_should_passed' => 'adv_audit.plugin.' . $this->id() . '.config.check_should_passed',
      'domain' => 'adv_audit.plugin.' . $this->id() . '.config.domain',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $settings = $this->getSettings();
    $current_domain = isset($settings['domain']) ? $settings['domain'] : $this->request->getHost();
    $options = [
      'query' => [
        'host' => $current_domain,
        'all' => 'done',
        'maxAge' => 1,
      ],
    ];
    $ssllab_analyze_url = Url::fromUri(static::SSLLAB_API_URL . static::ANALYZE_API_CALL, $options)
      ->toString();
    try {
      $response = $this->httpClient->request('GET', $ssllab_analyze_url);
      $result = json_decode($response->getBody());
    } catch (RequestException $e) {
      throw new RequirementsException($e->getMessage(), ['ssllab_check']);
    }
    // Wait until report will be ready.
    if (!in_array($result->status, ['READY', 'ERROR'])) {
      // NOSONAR.
      sleep(10);
      return $this->perform();
    }
    $report_options = [
      'query' => [
        'd' => $current_domain,
      ],
    ];
    $report_link = Link::fromTextAndUrl($this->t('link'), Url::fromUri(static::REPORT_URL, $report_options))
      ->toString();

    if ($result->status == 'ERROR') {
      return $this->skip($this->t('Check of SSL is failed with ERROR. Status message:') . ' ' . $result->statusMessage);
    }

    foreach ($result->endpoints as $endpoint) {
      if (!in_array($endpoint->grade, ['A', 'A+'])) {
        return $this->fail(NULL, [
          'issues' => [
            'ssllab_check' => [
              '@issue_title' => 'SSL check is failed.',
            ],
          ],
          '%link' => $report_link
        ]);
      }
    }
    return $this->success(['%link' => $report_link]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain for checking'),
      '#default_value' => $settings['domain'],
      '#description' => $this->t('You could specify this value in case of you need to check specific domain. Could be used for testing purpose or if we need to test canonical domain.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    // Just check that we are able to send requests to SslLabs.
    try {
      $this->httpClient->request('GET', static::SSLLAB_API_URL . static::INFO_API_CALL);
    }
    catch (RequestException $e) {
      throw new RequirementsException($e->getMessage(), ['ssllab_check']);
    }

    if (PHP_SAPI === 'cli' && !$this->state->get($this->buildStateConfigKeys()['domain'], FALSE)) {
      throw new RequirementsException('Can\'t define domain for check', ['ssllab_check']);
    }
  }

}
