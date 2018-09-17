<?php

namespace Drupal\adv_audit\Commands;

use Drupal\adv_audit\Batch\AuditRunBatch;
use Drupal\adv_audit\Plugin\AuditPluginsManager;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for adv_audit.
 */
class AuditCommands extends DrushCommands {

  protected $advAuditCheck;

  /**
   * Initialize AuditPluginsManager.
   */
  public function __construct(AuditPluginsManager $plugin_manager_adv_audit_check) {
    $this->advAuditCheck = $plugin_manager_adv_audit_check;
  }

  /**
   * Run Batch process for audit.
   *
   * @command adv_audit:adv-audit-run
   * @aliases adv_run
   */
  public function advAuditRunBatch() {
    // Run AuditChecks implemented via plugins.
    $tests = $this->advAuditCheck->getDefinitions();
    $batch = [
      'title' => 'Running process audit',
      'init_message' => 'Prepare to process.',
      'progress_message' => 'Progress @current out of @total.',
      'error_message' => 'An error occurred. Rerun the process or consult the logs.',
      'operations' => [
        [
          [AuditRunBatch::class, 'run'],
          [array_keys($tests), []],
        ],
      ],
      'finished' => [
        AuditRunBatch::class, 'finished',
      ],
    ];

    batch_set($batch);

    drush_backend_batch_process();
  }

}
