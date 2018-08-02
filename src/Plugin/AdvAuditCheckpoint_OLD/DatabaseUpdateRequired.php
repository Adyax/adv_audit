<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;

/**
 * Check the Drupal core version and its actuality.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "database_update_required",
 *   label = @Translation("No database updates required"),
 *   description = @Translation("Check database state."),
 *   category = "core_and_modules",
 *   status = TRUE,
 *   severity = "critical"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class DatabaseUpdateRequired extends AdvAuditCheckpointBase {

  protected $actionMessage = 'Apply all pending database updates.';

  protected $impactMessage = 'This point is very important and if do not apply all pending database updates for all modules it can lead to unstable work of project and finally can broke website at all.';

  protected $successMessage = 'Up to date';

  protected $failMessage = 'Database need to be updated.';

  protected $additionalServices = [
    'systemManager' => 'system.manager',
  ];

  /**
   * {@inheritdoc}
   */
  public function help() {
    return $this->t('Check database state.');
  }

  /**
   * Process checkpoint review.
   */
  public function process() {
    $requirements = $this->systemManager->listRequirements();
    if (isset($requirements['update']['severity'])) {
      $this->setProcessStatus($this::FAIL);
    }

    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->get('result_desTitlecription'),
      'information' => $this->getProcessResult(),
      'status' => $this->getProcessStatus(),
      'severity' => $this->get('severity'),
      'actions' => $this->getActions(),
      'impacts' => $this->getImpacts(),
    ];

    $results[$this->get('category')][$this->getPluginId()] = $result;
    return $results;
  }

}
