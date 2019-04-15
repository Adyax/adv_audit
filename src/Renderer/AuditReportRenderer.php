<?php

namespace Drupal\adv_audit\Renderer;

use Drupal\adv_audit\Service\AuditCategoryManagerService;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\adv_audit\Plugin\AuditPluginsManager;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class Renderer to build audit response object.
 */
class AuditReportRenderer implements RenderableInterface {

  use StringTranslationTrait;

  /**
   * The renderer service
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Audit Plugin manager service.
   *
   * @var \Drupal\adv_audit\Plugin\AuditPluginsManager
   */
  protected $pluginManagerAdvAuditCheck;

  /**
   * The Audit Message Storage.
   *
   * @var \Drupal\adv_audit\Message\AuditMessagesStorageInterface
   */
  protected $advAuditMessages;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Audit Result Response storage
   *
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
   * The category manager service.
   *
   * @var \Drupal\adv_audit\Service\AuditCategoryManagerService
   */
  protected $categoryManager;

  /**
   * Current entity view mode.
   *
   * @var string
   */
  protected $auditResultViewMode;

  /**
   * Current entity id.
   *
   * @var string
   */
  protected $auditReportId;

  /**
   * Constructs a new Renderer object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal render service.
   * @param \Drupal\adv_audit\Plugin\AuditPluginsManager $plugin_manager_adv_audit_check
   *   Drupal render service.
   * @param \Drupal\adv_audit\Message\AuditMessagesStorageInterface $adv_audit_messages
   *   Drupal render service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal render service.
   * @param \Drupal\adv_audit\Service\AuditCategoryManagerService $category_manager
   *   The Audit Category manager service.
   */
  public function __construct(RendererInterface $renderer, AuditPluginsManager $plugin_manager_adv_audit_check, AuditMessagesStorageInterface $adv_audit_messages, ConfigFactoryInterface $config_factory, AuditCategoryManagerService $category_manager) {
    $this->renderer = $renderer;
    $this->pluginManagerAdvAuditCheck = $plugin_manager_adv_audit_check;
    $this->advAuditMessages = $adv_audit_messages;
    $this->configFactory = $config_factory;
    $this->categoryManager = $category_manager;
  }

  /**
   * Set result object to process.
   *
   * @param \Drupal\adv_audit\AuditResultResponseInterface $audit_result
   *   The result audit object.
   * @param string $view_mode
   *   The current entity view mode.
   * @param string $adv_audit_id
   *   The current entity id.
   *
   * @return $this
   *   Return itself for chaining.
   */
  public function setAuditResult(AuditResultResponseInterface $audit_result, $view_mode, $adv_audit_id) {
    $this->auditResultResponse = $audit_result;
    $this->auditResultViewMode = $view_mode;
    $this->auditReportId = $adv_audit_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    $adv_audit_id = $this->auditReportId;
    $view_mode = $this->auditResultViewMode;
    return [
      '#theme' => 'adv_audit_report_object__' . $view_mode,
      '#score_point' => $this->auditResultResponse->calculateScore(),
      '#title' => $this->t('Audit Report result'),
      '#categories' => $this->doBuildCategory(),
      '#global_info' => $this->auditResultResponse->getOverviewInfo(),
      '#adv_audit_id' => $adv_audit_id,
      '#attached' => [
        'library' => [
          'adv_audit/adv_audit.report',
        ],
      ],
    ];

  }

  /**
   * Build categories list.
   *
   * @return array
   *   List of properties to render.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function doBuildCategory() {
    $build = [];
    foreach ($this->categoryManager->getListOfCategories() as $category_id => $category_label) {
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
        switch ($reason->getStatus()) {
          case AuditResultResponseInterface::RESULT_PASS:
            $build[$category_id]['reports_passed'][$plugin_id] = $this->doBuildAuditReason($reason);
            break;

          case AuditResultResponseInterface::RESULT_FAIL:
            // Check reported issues.
            $reported_issues = $reason->getOpenIssues();
            if (empty($reported_issues)) {
              // All the ignored reports are initially failed.
              $build[$category_id]['reports_ignored'][$plugin_id] = $this->doBuildAuditReason($reason);
              break;
            }
            $build[$category_id]['reports_failed'][$plugin_id] = $this->doBuildAuditReason($reason);
            break;

          case AuditResultResponseInterface::RESULT_SKIP:
            $build[$category_id]['reports_skipped'][$plugin_id] = $this->doBuildAuditReason($reason);
            break;

          default:
            break;
        }
      }
    }

    return $build;
  }

  /**
   * Get Audit Reason object by plugin id.
   *
   * @param string $plugin_id
   *   The plugin id.
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
   * @param string $category_id
   *   The category id.
   *
   * @return int
   *   The score value.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
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
      /** @var \Drupal\adv_audit\Plugin\AuditBasePlugin $plugin_instance */
      $plugin_instance = $this->pluginManagerAdvAuditCheck->createInstance($audit_reason->getPluginId());
      if ($plugin_instance->getCategoryName() == $category_id) {
        $plugins_list[] = $plugin_instance->id();
        // Increase a total checks counter.
        if ($audit_reason->getStatus() == AuditResultResponseInterface::RESULT_SKIP) {
          // Skip.
          continue;
        }
        $total_count++;
        if ($audit_reason->getStatus() == AuditResultResponseInterface::RESULT_PASS) {
          $passed++;
        }
        if ($audit_reason->getStatus() == AuditResultResponseInterface::RESULT_FAIL) {
          // Check active issues.
          $open_issues = $audit_reason->getOpenIssues();
          if (empty($open_issues)) {
            $passed++;
          }
        }
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
   *   The Audit Reason object.
   *
   * @return array
   *   The list or rendereable properties.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function doBuildAuditReason(AuditReason $audit_reason) {
    $build = [];
    // Init plugin instance.
    $audit_plugin_id = $audit_reason->getPluginId();
    /** @var \Drupal\adv_audit\Plugin\AuditBasePlugin $plugin_instance */
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
        unset($build[AuditMessagesStorageInterface::MSG_TYPE_ACTIONS]);
        unset($build[AuditMessagesStorageInterface::MSG_TYPE_IMPACTS]);
        break;

      case AuditResultResponseInterface::RESULT_FAIL:
        // Check reported issues.
        $reported_issues = $audit_reason->getOpenIssues();
        if (empty($reported_issues)) {
          $build['result_attributes']->addClass('status-ignored');
        }
        else {
          $build['result_attributes']->addClass('status-failed');
        }
        $build['result'] = $this->doRenderIssues($plugin_instance, $audit_reason, AuditMessagesStorageInterface::MSG_TYPE_FAIL);
        $build['reason'] = $audit_reason->getReason();
        break;

      default:
        $build['result_attributes']->addClass('status-skipped');
        $build['reason'] = $audit_reason->getReason();
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
   * @param \Drupal\adv_audit\Plugin\AuditBasePlugin $plugin_instance
   *   The audit plugin instance.
   * @param \Drupal\adv_audit\AuditReason $audit_reason
   *   The Audit reason object.
   * @param string $msg_type
   *   The type of built message.
   *
   * @return array
   *   The built message.
   *
   * @throws \Exception
   */
  protected function doRenderMessages(AuditBasePlugin $plugin_instance, AuditReason $audit_reason, $msg_type) {
    // Check what we can delivery build message to plugin instance.
    if ($plugin_instance instanceof AuditReasonRenderableInterface) {
      $render = $plugin_instance->auditReportRender($audit_reason, $msg_type);
      if (!empty($render)) {
        $this->renderer->render($render);
        return $render;
      }
    }
    // Get needed message from yml config file.
    // Replace dynamic variables.
    $details = is_array($audit_reason->getArguments()) ? $audit_reason->getArguments() : [];
    $msg_string = $this->advAuditMessages->replacePlaceholder($plugin_instance->id(), $msg_type, $details);
    return [
      '#markup' => $msg_string,
    ];
  }

  /**
   * Render output messages.
   *
   * @param \Drupal\adv_audit\Plugin\AuditBasePlugin $plugin_instance
   *   The audit plugin instance.
   * @param \Drupal\adv_audit\AuditReason $audit_reason
   *   The Audit reason object.
   * @param string $msg_type
   *   The type of built message.
   *
   * @return array
   *   The built message.
   *
   * @throws \Exception
   */
  protected function doRenderIssues(AuditBasePlugin $plugin_instance, AuditReason $audit_reason, $msg_type) {
    // Get needed message from yml config file.
    // And Replace dynamic variables.
    $details = is_array($audit_reason->getArguments()) ? $audit_reason->getArguments() : [];
    $message = $this->advAuditMessages->replacePlaceholder($plugin_instance->id(), $msg_type, $details);

    $all_issues = $audit_reason->getIssues();
    if (empty($all_issues)) {
      return [];
    }

    $active_rows = [];
    $ignored_rows = [];

    foreach ($all_issues as $issue) {
      $url = Url::fromRoute('adv_audit.issue_change_status', ['adv_audit_id' => $this->auditReportId, 'issue' => $issue->id->value]);
      if ($issue->isOpen()) {
        $active_rows[] = [
          $issue->getMarkup(),
          Link::fromTextAndUrl('Ignore', $url),
        ];
      }
      else {
        $ignored_rows[] = [
          $issue->getMarkup(),
          Link::fromTextAndUrl('Reopen', $url),
        ];
      }
    }

    $output = [
      '#markup' => $message,
    ];

    if (!empty($active_rows)) {
      $output['active_issues'] = [
        '#theme' => 'table',
        '#caption' => $this->t('Active issues'),
        '#header' => [
          ['data' => $this->t('Issue')],
          ['data' => $this->t('Edit'), 'width' => '15%'],
        ],
        '#rows' => $active_rows,
      ];
    }
    if (!empty($ignored_rows)) {
      $output['ignored_issues'] = [
        '#theme' => 'table',
        '#caption' => $this->t('Ignored issues'),
        '#header' => [
          ['data' => $this->t('Issue')],
          ['data' => $this->t('Edit'), 'width' => '15%'],
        ],
        '#rows' => $ignored_rows,
      ];
    }

    return $output;
  }

}
