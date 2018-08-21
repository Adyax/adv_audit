<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @AdvAuditCheck(
 *  id = "ssllab_check",
 *  label = @Translation("SSL test"),
 *  category = "security",
 *  severity = "high",
 *  enabled = true,
 * )
 */
class SslCheckPlugin extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, AdvAuditReasonRenderableInterface {

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
   * Constructs a new ExampleAuditCheckPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
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
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.config.check_should_passed';
  }
  
  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigDomain() {
    return 'adv_audit.plugin.' . $this->id() . '.config.domain';
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {        
    if ($this->state->get($this->buildStateConfigKey()) == 1) {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
    }

    $predefined_domain = $this->state->get($this->buildStateConfigDomain(), FALSE);
    $current_domain = $predefined_domain ? $predefined_domain : $this->request->getHost();
    $options = [
      'query' => [
        'host' => $current_domain,
        'all' => 'done',
        'maxAge' => 1,
      ]
    ];
    $ssllab_analyze_url = Url::fromUri(self::SSLLAB_API_URL . self::ANALYZE_API_CALL, $options)->toString();
    try {
      $response = $this->httpClient->request('GET', $ssllab_analyze_url);
      $result = json_decode($response->getBody());
    }
    catch (RequestException $e) {
      throw new RequirementsException($e->getMessage(), ['ssllab_check']);
    }
    // Wait until report will be ready.
    if (!in_array($result->status, ['READY', 'ERROR'])) {
      sleep(10);
      return $this->perform();
    }
    $report_options = [
      'query' => [
        'd' => $current_domain,
      ]
    ];
    $report_url = Url::fromUri(self::REPORT_URL, $report_options);
    
    if ($result->status == 'ERROR') {
      return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, 'Check of SSL is failed with ERROR. For more details please visit @link', ['@link' => Link::fromTextAndUrl($this->t('link'), $report_url)]);
    }
    
    foreach ($result->endpoints as $endpoint) {
      if (!in_array($endpoint->grade, ['A', 'A+'])) {
        return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_FAIL, 'Check of SSL is failed. For more details please visit @link', ['@link' => Link::fromTextAndUrl($this->t('link'), $report_url)]);
      }
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS, 'Check of SSL is passed. Please check @link', ['@link' => Link::fromTextAndUrl($this->t('link'), $report_url)]);
  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {
    $form['check_should_passed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select this if check should passed'),
      '#default_value' => $this->state->get($this->buildStateConfigKey()),
    ];
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain for checking'),
      '#default_value' => $this->state->get($this->buildStateConfigDomain()),
      '#description' => $this->t('You could specify this value in case of you need to check specific domain. Could be used for testing purpose or if we need to test canonical domain.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    // Get value from form_state object and save it.
    $check_should_passed = $form_state->getValue(['additional_settings', 'plugin_config', 'check_should_passed'], 0);
    $domain = $form_state->getValue(['additional_settings', 'plugin_config', 'domain'], 0);

    $this->state->set($this->buildStateConfigKey(), $check_should_passed);
    $this->state->set($this->buildStateConfigDomain(), $domain);
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    // Please be careful.
    // Before extend check Requirements method you should call parent method before.
    parent::checkRequirements();
    // Just check that we are able to send requests to SslLabs and don't have impediments on server side.
    try {
      $service_check = $this->httpClient->request('GET', self::SSLLAB_API_URL . self::INFO_API_CALL);
    }
    catch (RequestException $e) {
      throw new RequirementsException($e->getMessage(), ['ssllab_check']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    switch ($type) {
      // Override messages fail output.
      // In this case, we will not use messages from messages.yml file and directly will render what you return.
      case AuditMessagesStorageInterface::MSG_TYPE_FAIL:
        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['custom-fail-color'],
          ],
          'message' => [
            '#markup' => $this->t($this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_FAIL), $reason->getArguments())->__toString(),
          ],
        ];
        break;

      // Override messages success output.
      // At this moment you have fully control what how will build success messages.
      case AuditMessagesStorageInterface::MSG_TYPE_SUCCESS:
        $build = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['custom-pass-color'],
          ],
          'message' => [
            '#markup' => $this->t($this->messagesStorage->get($this->id(), AuditMessagesStorageInterface::MSG_TYPE_SUCCESS), $reason->getArguments())->__toString(),
          ],
        ];
        break;

      default:
        // Return empty array.
        // In this case will display messages from messages.yml file for you plugin.
        $build = [];
        break;
    }

    return $build;
  }

}
