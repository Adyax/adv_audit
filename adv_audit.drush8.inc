<?php

/**
 * @file
 * Drush command for adv_audit.
 */

use Drupal\adv_audit\Batch\AuditRunTestBatch;

/**
 * Implements hook_drush_command().
 */
function adv_audit_drush_command() {

  $commands['adv-audit-run'] = [
    'description' => 'Run batch for get audit results',
    'aliases' => ['adv_run'],
    'arguments' => [],
    'options' => [],
    'examples' => [],
  ];

  return $commands;
}

/**
 * Logic of drush command.
 */
function drush_adv_audit_run() {
  // Run AuditChecks implemented via plugins.
  $tests = \Drupal::service('plugin.manager.adv_audit_check')->getDefinitions();
  $batch = [
    'title' => 'Running process audit',
    'init_message' => 'Prepare to process.',
    'progress_message' => 'Progress @current out of @total.',
    'error_message' => 'An error occurred. Rerun the process or consult the logs.',
    'operations' => [
      [
        [AuditRunTestBatch::class, 'run'],
        [array_keys($tests), []],
      ],
    ],
    'finished' => [
      AuditRunTestBatch::class, 'finished',
    ],
  ];
  batch_set($batch);
  drush_backend_batch_process();

  drush_print(dt('Batch was running.'));
}
