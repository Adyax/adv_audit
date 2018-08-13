<?php

namespace Drupal\adv_audit\Renderer;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupla\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

/**
 * Class Renderer.
 */
class AuditReportRenderer implements RenderableInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;
  /**
   * Drupal\adv_audit\Plugin\AdvAuditCheckManager definition.
   *
   * @var \Drupal\adv_audit\Plugin\AdvAuditCheckManager
   */
  protected $pluginManagerAdvAuditCheck;
  /**
   * Drupal\adv_audit\Message\AuditMessagesStorageInterface definition.
   *
   * @var \Drupal\adv_audit\Message\AuditMessagesStorageInterface
   */
  protected $advAuditMessages;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\adv_audit\AuditResultResponse
   */
  protected $auditResultResponse;

  /**
   * Constructs a new Renderer object.
   */
  public function __construct(RendererInterface $renderer, AdvAuditCheckManager $plugin_manager_adv_audit_check, AuditMessagesStorageInterface $adv_audit_messages, ConfigFactoryInterface $config_factory) {
    $this->renderer = $renderer;
    $this->pluginManagerAdvAuditCheck = $plugin_manager_adv_audit_check;
    $this->advAuditMessages = $adv_audit_messages;
    $this->configFactory = $config_factory;
  }

  public function setAuditResult(AuditResultResponseInterface $audit_result) {
    $this->auditResultResponse = $audit_result;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    $build = [
      '#type' => 'container',
      '#attrbutes' => [
        'class' => ['audit-report-container'],
      ],
    ];
    $build['header'] = [
      '#type' => 'container',
      '#attrbiutes' => [
        'class' => ['header']
      ],
      'title' => [
        '#theme' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Audit Report result')
      ],
    ];
    $build['scope']['#markup'] = 'Score: ' . $this->auditResultResponse->calculateScore();
    $build['categories'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['']
      ],
    ];

    // Build list of availabled categories.
    foreach ($this->getCategoriesList() as $category_id => $category_label) {

    }
    return $build;
  }

  protected function getCategoriesList() {
    $audit_config = $this->configFactory->get('adv_audit.config');
    $categories = $audit_config->get('adv_audit_settings.categories');
    $list = [];
    foreach ($categories as $id => $cat_definition) {
      if ($cat_definition['status']) {
        $list[$id] = $cat_definition['label'];
      }
    }
    return $list;
  }

  protected function doBuildCategory($category_id, $category_label) {

  }

  /**
   * Build audit reason.
   *
   * @param \Drupal\adv_audit\AuditReason $audit_reason
   */
  protected function doBuildAuditReason(AuditReason $audit_reason) {
    $build = [
      '#theme' => 'adv_audit_reason'
    ];
    // Init plugin instance.
    $plugin_instance = $this->pluginManagerAdvAuditCheck->createInstance($audit_reason->getPluginId());


  }


  /**
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckBase $plugin_instance
   *   The audit plugin instance.
   * @param \Drupal\adv_audit\AuditReason $audit_reason
   *
   * @param $msg_type
   *
   * @return array
   */
  protected function doRenderMessages($plugin_instance, $audit_reason, $msg_type) {
    // Check what we can delivery build message to plugin instance.
    if ($plugin_instance instanceof AdvAuditReasonRenderableInterface) {
      $render = $plugin_instance->auditReportRender($audit_reason, $msg_type);
      return $render;
    }
    // Get needed message from yml config file.
    $msg_string = $this->advAuditMessages->get($plugin_instance->id(), $msg_type);
    // Replace dynamic variables.
    $msg_string = strtr($msg_string, $audit_reason->getArguments());
    return ['#markup' => $msg_string];
  }

}
