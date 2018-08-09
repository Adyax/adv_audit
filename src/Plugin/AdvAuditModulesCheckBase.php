<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\adv_audit\AuditReason;

/**
 * Base class for Advances audit modules updates check plugins.
 */
abstract class AdvAuditModulesCheckBase extends AdvAuditCheckBase {

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
   * Prepares audit reason arguments for displaying
   *
   * @param object  \Drupal\adv_audit\AuditReason $reason
   *   A configuration array containing information about the plugin instance.
   *
   * @return array
   *   An array that contains elements which are strings or renderable arrays.
   */
  public function auditReportRender(AuditReason $reason) {
    $arguments = $reason->getArguments();
    if (empty($arguments)) {
      return [];
    }

    if (!empty($arguments['@list'])) {
      $render_list = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Installed version'),
          $this->t('Recommended version'),
        ],
        '#rows' => $arguments['@list'],
      ];
    }
    else {
      $render_list = [];
    }

    return [
      '@link' => $arguments['@link']->toString(),
      '@count' => $this->stringTranslation->formatPlural($arguments['@count'], '1 module', '@count modules'),
      '@list' => $render_list
    ];
  }

}
