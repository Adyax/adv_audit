<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\hacked\Controller\HackedController;

/**
 * Check if some modules were patched.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "adv_audit_patched_modules",
 *   label = @Translation("Patched modules."),
 *   description = @Translation("Provide tool to control codebase state."),
 *   category = "core_and_modules",
 *   status = FALSE,
 *   severity = "high"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class PatchedModulesCheck extends AdvAuditCheckpointBase {

  protected $actionMessage = 'Review patched modules and make sure, that every change could be restored from patch has been stored in file.';

  protected $failMessage = '';

  protected $successMessage = 'All modules are original.';

  protected $impactMessage = 'Without storing patches in files, web-site could be broken during updates.';

  protected $additionalServices = [
    'stringTranslation' => 'string_translation',
  ];

  /**
   * Return information about next actions.
   *
   * @return mixed
   *   Associated array.
   */
  public function getActions($params = []) {
    $link = Link::fromTextAndUrl('There', Url::fromRoute('hacked.report'));
    $params = ['@link' => $link->toString()];
    return parent::getActions($params);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return $this->t('Check if core and contribution modules were hacked.');
  }

  /**
   * Call to generate Hacked report.
   *
   * @param string $type
   *   Action type.
   *
   * @throws \Exception
   */
  protected function generateHackedReportMessage($type = 'install') {
    switch ($type) {

      case 'report':
        $link = Link::fromTextAndUrl('report generating', Url::fromRoute('hacked.report'))
          ->toRenderable();
        $message = $this->stringTranslation->translate('Run @link to include this verification into your report.', [
          '@link' => $this->renderer->render($link),
        ]);
        break;

      default:
        $link = Link::fromTextAndUrl('hacked', Url::fromUri('https://www.drupal.org/project/hacked'))
          ->toRenderable();
        $message = $this->stringTranslation->translate('Please, install @link module to enable this verification.', [
          '@link' => $this->renderer->render($link),
        ]);
        break;
    }
    $this->messenger->addMessage($message, 'warning');
  }

  /**
   * {@inheritdoc}
   */
  protected function validation() {
    $is_validated = parent::validation();
    if (!$is_validated) {
      $this->generateHackedReportMessage('install');
    }
    if ($is_validated) {
      $hacked = new HackedController();
      $hacked = $hacked->hackedStatus();
      $is_validated = is_array($hacked) && isset($hacked['#data']) ? TRUE : FALSE;
      $this->generateHackedReportMessage('report');
    }
    return $is_validated;
  }

  /**
   * Process checkpoint review.
   */
  public function process() {
    $params = [];
    $hacked = new HackedController();
    $hacked = $hacked->hackedStatus();
    $is_hacked = FALSE;
    foreach ($hacked['#data'] as $project) {
      if ($project['counts']['different'] != 0) {
        $is_hacked = TRUE;
      }
    }

    if ($is_hacked) {
      $this->setProcessStatus('fail');
      $link = Link::fromTextAndUrl('There', Url::fromRoute('hacked.report'));
      $params = ['@link' => $link->toString()];
    }
    else {
      $this->setProcessStatus('success');
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

  /**
   * {@inheritdoc}
   */
  protected function getRequirements() {
    return ['modules' => ['hacked']];
  }

}
