<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

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
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_ACTIONS) {
      $arguments = $reason->getArguments();
      if (empty($arguments)) {
        return [];
      }

      $list_key = '@list';

      if (!empty($arguments[$list_key])) {
        $render_list = [
          '#type' => 'table',
          '#header' => [
            $this->t('Name'),
            $this->t('Installed version'),
            $this->t('Recommended version'),
          ],
          '#rows' => $arguments[$list_key],
        ];
      }
      else {
        $render_list = [];
      }

      return [
        'link' => ['#markup' => $arguments['@link']->toString()],
        'count' => ['#markup' => $this->stringTranslation->formatPlural($arguments['@count'], '1 module', '@count modules')],
        'list' => $render_list
      ];
    }

    return [];
  }

}
