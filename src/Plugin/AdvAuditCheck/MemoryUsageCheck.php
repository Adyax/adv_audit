<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Bytes;

/**
 * Checks memory usage for pre-defined pages.
 *
 * @AdvAuditCheck(
 *   id = "memory_usage",
 *   label = @Translation("Memory usage check"),
 *   category = "performance",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class MemoryUsageCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, ContainerFactoryPluginInterface {
  /**
   * Symfony\Component\HttpKernel\HttpKernelInterface definition.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The State API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HttpKernelInterface $http_kernel, StateInterface $state, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpKernel = $http_kernel;
    $this->state = $state;
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
      $container->get('http_kernel'),
      $container->get('state'),
      $container->get('request_stack')->getCurrentRequest()
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
      'urls' => 'adv_audit.plugin.' . $this->id() . '.config.urls',
      'mem' => 'adv_audit.plugin.' . $this->id() . '.config.memory_fail_treshold',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm() {
    $type_key = '#type';
    $form['urls'] = [
      '#title' => $this->t('URLs for memory usage checking'),
      '#description' => $this->t('Place one URL(relative) per line as relative with preceding slash. i.e /path/to/page'),
      '#default_value' => $this->state->get($this->buildStateConfigKeys()['urls']),
      '#required' => TRUE,
    ];
    $form['urls'][$type_key] = 'textarea';

    $current_limit = ini_get('memory_limit');
    $form['mem'] = [
      '#title' => $this->t('Memory treshold for fail'),
      '#description' => $this->t('Set value(without % symbol) that indicates part(in percents) of total memory limit, i.e 15.
        If one of the listed URLs consumes more than given treshold check will be cosidered as failed.
        Current limit is @limit', ['@limit' => $current_limit]),
      '#default_value' => $this->state->get($this->buildStateConfigKeys()['mem']),
      '#field_suffix' => '%',
      '#required' => TRUE,
      '#size' => 10,
    ];
    $form['mem'][$type_key] = 'textfield';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    $base = ['additional_settings', 'plugin_config'];
    $value = $form_state->getValue(array_merge($base, ['urls']));
    $this->state->set($this->buildStateConfigKeys()['urls'], $value);

    $value = $form_state->getValue(array_merge($base, ['mem']));
    $this->state->set($this->buildStateConfigKeys()['mem'], $value);
  }

  /**
   * {@inheritdoc}
   */
  public function configFormValidate($form, FormStateInterface $form_state) {
    $base = ['additional_settings', 'plugin_config'];
    $urls = $this->parseLines($form_state->getValue(array_merge($base, ['urls'])));

    foreach ($urls as $url) {
      if (!UrlHelper::isValid($url) || substr($url, 0, 1) !== '/') {
        $form_state->setErrorByName('additional_settings][plugin_config][urls', $this->t('Urls should be given as relative with preceding slash.'));
        break;
      }
    }

    $value = $form_state->getValue(array_merge($base, ['mem']));
    if (!is_numeric($value) || $value <= 0) {
      $form_state->setErrorByName(
        'additional_settings][plugin_config][mem',
        $this->t('Memory treshold should be positive numeric.')
      );
    }
  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $params = [];
    $urls = $this->parseLines($this->state->get($this->buildStateConfigKeys()['urls']));

    $memory_treshold = $this->state->get($this->buildStateConfigKeys()['mem']) / 100;
    $total_memory = intval(ini_get('memory_limit'));

    if ($total_memory <= 0) {
      $reason = $this->t('Memory limit has value @value. Looks like server is not correctly configured.',
        ['@value' => $total_memory]);
      return $this->skip($reason);
    }

    $total_memory = Bytes::toInt($total_memory);

    foreach ($urls as $url) {
      $sub_request = Request::create($this->request->getSchemeAndHttpHost() . $url, 'GET');
      if ($this->request->getSession()) {
        $sub_request->setSession($this->request->getSession());
      }
      $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);

      $memory = memory_get_peak_usage(TRUE);

      if ($memory / $total_memory > $memory_treshold) {
        $params['failed_urls'][$url] = format_size($memory)->render();
      }
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
    if (empty($issue_details['failed_urls'])) {
      return [];
    }

    array_walk($issue_details['failed_urls'], function (&$value, &$key) {
      $value = $key . ': ' . $value;
    });

    return [
      '#type' => 'container',
      'msg' => [
        '#markup' => $this->t('There are URLs with big memory usage.'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => $issue_details['failed_urls'],
      ],
    ];
  }

  /**
   * Parses textarea lines into array.
   *
   * @param string $lines
   *   Textarea content.
   *
   * @return array
   *   The textarea lines.
   */
  private function parseLines($lines) {
    $lines = explode("\n", $lines);

    if (!count($lines)) {
      return [];
    }
    $lines = array_filter($lines, 'trim');

    return str_replace("\r", "", $lines);
  }

}
