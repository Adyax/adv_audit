<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

/**
 * Base class for Advances audit modules updates check plugins.
 */
abstract class AdvAuditModulesCheckBase extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface {

  /**
   * Store modules list.
   *
   * @var array
   *   Updates list.
   */
  protected $updates = [];

  /**
   * Number of updates.
   *
   * @var mixed
   *   Modules count.
   */
  protected $count;

  /**
   * Drupal\update\UpdateManagerInterface definition.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $updateManager;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type != AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      return [];
    }

    $arguments = $reason->getArguments();
    if (empty($arguments)) {
      return [];
    }

    $message = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['fail-message'],
      ],
    ];
    $message['msg']['#markup'] = $this->t('There are outdated modules with updates.');

    if (!empty($arguments['list'])) {
      $render_list = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Installed version'),
          $this->t('Recommended version'),
        ],
        '#rows' => $arguments['list'],
      ];
    }
    else {
      $render_list = [];
    }

    return [$message, $render_list];
  }

}
