<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;

/**
 * Check if some modules were patched.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "cron_settings",
 *   label = @Translation("Cron settings."),
 *   description = @Translation("Provide tool to control codebase state."),
 *   category = "performance",
 *   status = FALSE,
 *   severity = "critical"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class CronSettings extends AdvAuditCheckpointBase {

  protected $actionMessage = 'Install and configure some advanced cron module.';

  protected $failMessage = '@process_result';

  protected $impactMessage = ' If system cron doesn’t work or/and Drupal cron was setted up incorrectly it can lead to a lot of problems: 
Drupal will not provide system-wide defaults to running jobs at particular times, storing (caching) web pages to improve efficiency, and performing other essential tasks.
Drupal will not periodically clean up log files
application will not have a possibility automatically update feeds
update manager will not have a possibility to check automatically pending updates
search indexes that also uses cron will not index new/updated content 
and many other points';

  protected $additionalServices = [
    'systemManager' => 'system.manager',
    'stringTranslation' => 'string_translation',
  ];

  protected $advincedModules = [
    'ultimate_cron',
  ];

  /**
   * Length of the day in seconds.
   */
  const DAYTIMESTAMP = 86400;

  /**
   * {@inheritdoc}
   */
  public function help() {
    $this->process();
    return $this->t('Check cron settings.');
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessResult($params = []) {

    $list_actions = [];

    $list_actions[] = $params['last_run']['value'];

    if (!isset($params['adv_module'])) {
      $list_actions[] = $this->stringTranslation->translate('Any of suggested modules isn\'t installed: @list', ['@list' => implode(', ', $this->advincedModules)]);
    }

    $placeholder = [
      '#theme' => 'item_list',
      '#items' => $list_actions,
    ];

    $params['@process_result'] = $this->renderer->render($placeholder);

    return parent::getProcessResult($params);
  }

  /**
   * Process checkpoint review.
   */
  public function process() {

    $requirements = $this->systemManager->listRequirements();

    $params = [
      'last_run' => $requirements['cron'],
    ];

    $adv_cron = FALSE;
    foreach ($this->advincedModules as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        $adv_cron = TRUE;
        $params['adv_module'] = $module;
        break;
      }
    }

    if (!$adv_cron || isset($requirements['cron']['severity'])) {
      $this->setProcessStatus($this::FAIL);
    }

    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->get('result_description'),
      'information' => $this->getProcessResult($params),
      'status' => $this->getProcessStatus(),
      'severity' => $this->get('severity'),
      'actions' => $this->getActions(),
      'impacts' => $this->getImpacts(),
    ];

    $results[$this->get('category')][$this->getPluginId()] = $result;
    return $results;
  }

}
