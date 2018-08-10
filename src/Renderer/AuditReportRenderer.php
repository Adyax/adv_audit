<?php

namespace Drupal\adv_audit\Renderer;

use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class Renderer.
 */
class AuditReportRenderer implements RenderableInterface {

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
    $build = [];
    $build['scope']['#markup'] = 'Score: ' . $this->auditResultResponse->calculateScore();
    $build['categories'] = [
    ];
    return $build;
  }

}
