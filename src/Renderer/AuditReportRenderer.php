<?php

namespace Drupal\adv_audit\Renderer;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;

/**
 * Class Renderer to build audit response object.
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
   * Store additional information about current context.
   *
   * @var array
   */
  protected $metaInformation = [];

  /**
   * Constructs a new Renderer object.
   */
  public function __construct(RendererInterface $renderer, AdvAuditCheckManager $plugin_manager_adv_audit_check, AuditMessagesStorageInterface $adv_audit_messages, ConfigFactoryInterface $config_factory) {
    $this->renderer = $renderer;
    $this->pluginManagerAdvAuditCheck = $plugin_manager_adv_audit_check;
    $this->advAuditMessages = $adv_audit_messages;
    $this->configFactory = $config_factory;
  }

  /**
   * Set result object to process.
   *
   * @param \Drupal\adv_audit\AuditResultResponseInterface $audit_result
   *   The result audit object.
   *
   * @return $this
   *   Return itself for chaining.
   */
  public function setAuditResult(AuditResultResponseInterface $audit_result) {
    $this->auditResultResponse = $audit_result;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    $build = [
      '#theme' => 'adv_audit_report_object',
      '#score_point' => $this->auditResultResponse->calculateScore(),
      '#title' => $this->t('Audit Report result'),
      '#categories' => $this->doBuildCategory(),
      '#attached' => [
        'library' => [
          'adv_audit/adv_audit.report'
        ],
      ],
    ];

    $build['#score_point__status'] = 'success';
    $score_point = $build['#score_point'];

    if ($score_point <= 80 && $score_point >= 40) {
      $build['#score_point__status'] = 'warning';
    }
    elseif ($score_point < 40) {
      $build['#score_point__status'] = 'danger';
    }

    return $build;
  }

  /**
   * Get list of available categories.
   *
   * @return array
   *   List of categories.
   */
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

  /**
   * Build categories list.
   *
   * @return array
   *   List of properties to render.
   */
  protected function doBuildCategory() {
    $build = [];

    foreach ($this->getCategoriesList() as $category_id => $category_label) {
      $build[$category_id] = [
        'label' => $category_label,
        'score' => $this->calculateScoreByCategory($category_id),
        'id' => $category_id,
      ];
      if (isset($this->metaInformation['category'][$category_id]['stat'])) {
        $stat = $this->metaInformation['category'][$category_id]['stat'];
        $build[$category_id]['total'] = $stat['total'];
        $build[$category_id]['passed'] = $stat['passed'];
      }
      if (empty($this->metaInformation['category'][$category_id]['plugin_list'])) {
        // Remove already created category.
        unset($build[$category_id]);
        continue;
      }
      foreach ($this->metaInformation['category'][$category_id]['plugin_list'] as $plugin_id) {
        $reason = $this->getReasonByPluginId($plugin_id);
        $build[$category_id]['reports'][$plugin_id] = $this->doBuildAuditReason($reason);
      }
    }

    return $build;
  }

  /**
   * Get Audit Reason object by plugin id.
   *
   * @param string $plugin_id
   *    The plugin id.
   *
   * @return \Drupal\adv_audit\AuditReason
   *   The audit reason object.
   */
  protected function getReasonByPluginId($plugin_id) {
    /** @var \Drupal\adv_audit\AuditReason $reason */
    foreach ($this->auditResultResponse->getAuditResults() as $reason) {
      if ($reason->getPluginId() == $plugin_id) {
        return $reason;
      }
    }
  }

  /**
   * Calculate score value for selected category.
   *
   * TODO: Meybe we should move this to Response object.
   *
   * @param string $category_id
   *   The category id.
   *
   * @return int
   *   The score value.
   */
  protected function calculateScoreByCategory($category_id) {
    // Init default value for passed audit checks.
    $passed = 0;
    // At this moment we don't know how many audit check in the category.
    $total_count = 0;

    $plugins_list = [];

    /** @var \Drupal\adv_audit\AuditReason $audit_reason */
    foreach ($this->auditResultResponse->getAuditResults() as $audit_reason) {
      // Init plugin instance.
      /** @var \Drupal\adv_audit\Plugin\AdvAuditCheckBase $plugin_insatnce */
      $plugin_insatnce = $this->pluginManagerAdvAuditCheck->createInstance($audit_reason->getPluginId());
      if ($plugin_insatnce->getCategoryName() == $category_id) {
        // Increase a total checks counter.
        $total_count++;
        if ($audit_reason->getStatus() == AuditResultResponseInterface::RESULT_PASS) {
          $passed++;
        }
        $plugins_list[] = $plugin_insatnce->id();
      }
    }
    // Store meta information in variable for use.
    $this->metaInformation['category'][$category_id]['stat'] = [
      'total' => $total_count,
      'passed' => $passed,
    ];
    $this->metaInformation['category'][$category_id]['plugin_list'] = $plugins_list;
    if (!$total_count) {
      return 0;
    }
    $score = ($passed * 100) / $total_count;
    return intval($score);
  }

  /**
   * Build audit reason.
   *
   * @param \Drupal\adv_audit\AuditReason $audit_reason
   *   The adit reson object.
   *
   * @return array
   *   The list or rendereable properties.
   */
  protected function doBuildAuditReason(AuditReason $audit_reason) {
    $build = [];
    // Init plugin instance.
    $audit_plugin_id = $audit_reason->getPluginId();
    /** @var \Drupal\adv_audit\Plugin\AdvAuditCheckBase $plugin_instance */
    $plugin_instance = $this->pluginManagerAdvAuditCheck->createInstance($audit_plugin_id);
    // Build default messages type.
    $build[AuditMessagesStorageInterface::MSG_TYPE_DESCRIPTION] = $this->doRenderMessages($plugin_instance, $audit_reason, AuditMessagesStorageInterface::MSG_TYPE_DESCRIPTION);
    $build[AuditMessagesStorageInterface::MSG_TYPE_ACTIONS] = $this->doRenderMessages($plugin_instance, $audit_reason, AuditMessagesStorageInterface::MSG_TYPE_ACTIONS);
    $build[AuditMessagesStorageInterface::MSG_TYPE_IMPACTS] = $this->doRenderMessages($plugin_instance, $audit_reason, AuditMessagesStorageInterface::MSG_TYPE_IMPACTS);

    $build['result_attributes'] = new Attribute(['class' => ['audit-reason']]);
    // Build result message according status of check.
    switch ($audit_reason->getStatus()) {
      case AuditResultResponseInterface::RESULT_PASS:
        $build['result_attributes']->addClass('status-passed');
        $build['result'] = $this->doRenderMessages($plugin_instance, $audit_reason, AuditMessagesStorageInterface::MSG_TYPE_SUCCESS);
        break;

      case AuditResultResponseInterface::RESULT_FAIL:
        $build['result_attributes']->addClass('status-failed');
        $build['result'] = $this->doRenderMessages($plugin_instance, $audit_reason, AuditMessagesStorageInterface::MSG_TYPE_FAIL);
        break;

      default:
        $build['result_attributes']->addClass('status-skipped');
        break;
    }

    $plugin_definition = $plugin_instance->getPluginDefinition();
    $build['name'] = $plugin_definition['label'];
    $build['severity'] = $plugin_instance->getSeverityLevel();
    return $build;
  }


  /**
   * Render output messages.
   *
   * @param \Drupal\adv_audit\Plugin\AdvAuditCheckBase $plugin_instance
   *   The audit plugin instance.
   * @param \Drupal\adv_audit\AuditReason $audit_reason
   *   The Audit reason object.
   * @param string $msg_type
   *   The type of builded message.
   *
   *
   * @return array
   *   The builded message.
   *
   * @throws \Exception
   */
  protected function doRenderMessages(AdvAuditCheckBase $plugin_instance, AuditReason $audit_reason, $msg_type) {
    // Check what we can delivery build message to plugin instance.
    if ($plugin_instance instanceof AdvAuditReasonRenderableInterface) {
      $render = $plugin_instance->auditReportRender($audit_reason, $msg_type);
      if (!empty($render)) {
        $this->renderer->render($render);
        return $render;
      }
    }
    // Get needed message from yml config file.
    // Replace dynamic variables.
    $msg_string = $this->advAuditMessages->replacePlaceholder($plugin_instance->id(), $msg_type, $audit_reason->getArguments());
    return ['#markup' => $msg_string];
  }

}
