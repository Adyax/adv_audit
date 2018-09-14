<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

class MemoryUsageCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  use AuditPluginSubform;

  /**
   * Symfony\Component\HttpKernel\HttpKernelInterface definition.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HttpKernelInterface $http_kernel, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpKernel = $http_kernel;
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
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)  {
    $settings = $this->getSettings();
    $type_key = '#type';
    $form['urls'] = [
      '#title' => $this->t('URLs for memory usage checking'),
      '#description' => $this->t('Place one URL(relative) per line as relative with preceding slash. i.e /path/to/page'),
      '#default_value' => $settings['urls'],
      '#required' => TRUE,
    ];
    $form['urls'][$type_key] = 'textarea';

    $current_limit = ini_get('memory_limit');
    $form['mem'] = [
      '#title' => $this->t('Memory treshold for fail'),
      '#description' => $this->t('Set value(without % symbol) that indicates part(in percents) of total memory limit, i.e 15.
        If one of the listed URLs consumes more than given treshold check will be cosidered as failed.
        Current limit is @limit', ['@limit' => $current_limit]),
      '#default_value' => $settings['mem'],
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
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $urls = $this->parseLines($values['urls']);

    foreach ($urls as $url) {
      if (!UrlHelper::isValid($url) || substr($url, 0, 1) !== '/') {
        $form_state->setErrorByName('urls', $this->t('Urls should be given as relative with preceding slash.'));
        break;
      }
    }

    if (!is_numeric($values['mem']) || $values['mem'] <= 0) {
      $form_state->setErrorByName(
        'mem',
        $this->t('Memory treshold should be positive numeric.')
      );
    }
  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $params = [];
    $settings = $this->getSettings();
    $urls = $this->parseLines($settings['urls']);

    $memory_treshold = $settings['mem'] / 100;
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
      $issues = [];
      foreach ($params['failed_urls'] as $failed_url => $failed_url_memory) {
        $issues['memory_usage' . $failed_url] = [
          '@issue_title' => 'There is a page "@page" with big memory usage: @memory',
          '@page' => $failed_url,
          '@memory' => $failed_url_memory,
        ];
      }

      return $this->fail(NULL, ['issues' => $issues]);
    }

    return $this->success();
  }

}
