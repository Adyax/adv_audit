<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;

/**
 * Check the Drupal core version and its actuality.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "drupal_core",
 *   label = @Translation("Drupal core"),
 *   description = @Translation(""),
 *   category = "core_and_modules",
 *   status = TRUE,
 *   severity = "high"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class DrupalCore extends AdvAuditCheckpointBase {

  protected $actionMessage = 'Enable core aggregation or use @link (that includes all latest security updates).';

  protected $impactMessage = 'If you don’t monitor for new versions and ignore core updates, your application is in danger as hackers follow security-related incidents (which have to be published as soon as they\'re discovered) and try to exploit the known vulnerabilities. Also each new version of the Drupal core contains bug fixes, which increases the stability of the entire platform.';

  protected $failMessage = 'Current Drupal core version is outdated - @version';

  protected $successMessage = 'Current Drupal core version is up to date - @version';

  protected $resultDescription = 'Maintaining a Drupal site is not just about managing the content on the site and administering users and the configuration. An important part of maintaining a Drupal site is in keeping the site updated with the latest security updates released for Drupal core. Drupal is a very secure platform however this does not mean that a snapshot of the platform from a given point in time is free from all imaginable security loopholes. When a security vulnerability is identified by the community or the Drupal Security Team it will be taken care of promptly with the involvement of the Drupal Security Team and patches and a new security release of Drupal core is released within a very short period of time from the time the vulnerability is identified. Once the new security update for the module or Drupal core is released it would be the responsibility of each Drupal site owner (development team) to keep their site updated. A general recommendation is to update the site with all the security updates as soon as the security updates are released.';

  /**
   * Return information about process result.
   *
   * @return mixed
   *   Provide result of process.
   */
  public function getProcessResult() {
    if ($this->getProcessStatus() == 'fail') {
      return $this->get('fail_message') ? $this->t($this->get('fail_message'), ['@version' => $this->getCurrentVersion()]) : '';
    }
    return $this->get('success_message') ? $this->t($this->get('success_message'), ['@version' => $this->getRecommendedVersion()]) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return $this->t('Check the Drupal core version and its actuality');
  }

  /**
   * Return information about next actions.
   *
   * @return mixed
   *   Associated array.
   */
  public function getActions() {
    $params = ['@version' => $this->getRecommendedVersion()];
    return parent::getActions($params);
  }

  /**
   * Process checkpoint review.
   */
  public function process() {

    // Check updates for Drupal core.
    $project = [
      'name' => 'drupal',
      'project_type' => 'core',
      'includes' => [],
    ];

    \Drupal::service('update.processor')->processFetchTask($project);

    // Set process status 'fail' if current version is net recommended.
    if ($this->getCurrentVersion() != $this->getRecommendedVersion()) {
      $this->setProcessStatus('fail');
    }
    else {
      $this->setProcessStatus('success');
    }

    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->get('result_description'),
      'information' => $this->getProcessResult(),
      'status' => $this->getProcessStatus(),
      'severity' => $this->get('severity'),
      'actions' => $this->getActions(),
      'impacts' => $this->getImpacts(),
    ];

    \Drupal::logger('test results')
      ->notice('<pre>' . print_r($result, 1) . '</pre>');

    $results[$this->get('category')][$this->getPluginId()] = $result;
    return $results;
  }

  /**
   * Return current version of Drupal Core.
   *
   * @return mixed
   *   Returns current version of core.
   */
  protected static function getCurrentVersion() {
    $projects_data = \Drupal::service('update.manager')
      ->projectStorage('update_project_data');
    return $projects_data['drupal']['existing_version'];
  }

  /**
   * Return recommended version of Drupal Core.
   *
   * @return mixed
   *   Returns recommended version.
   */
  protected static function getRecommendedVersion() {
    $projects_data = \Drupal::service('update.manager')
      ->projectStorage('update_project_data');
    return $projects_data['drupal']['recommended'];
  }

}
