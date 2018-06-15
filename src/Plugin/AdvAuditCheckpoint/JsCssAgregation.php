<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\Core\Plugin\PluginBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckpointInterface;

/**
 * Check if agregation for js and css is enabled.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "js_css_agregation",
 *   label = @Translation("Javascript & CSS aggregation"),
 *   description = @Translation("Allows you to improve the frontend performance
 *   of your site."),
 *   category = "performance",
 *   status = TRUE,
 *   severity = "high"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class JsCssAgregation extends PluginBase implements AdvAuditCheckpointInterface {

  /**
   * Verification Status.
   *
   * @var status
   */
  protected $status;

  /**
   * Return description of current checkpoint.
   *
   * @return mixed
   *   Associated array.
   */
  public function getDescription() {
    return $this->t("Maintaining a Drupal site is not just about managing the content on the site and administering users and the configuration. An important part of maintaining a Drupal site is in keeping the site updated with the latest security updates released for Drupal core. Drupal is a very secure platform however this does not mean that a snapshot of the platform from a given point in time is free from all imaginable security loopholes. When a security vulnerability is identified by the community or the Drupal Security Team it will be taken care of promptly with the involvement of the Drupal Security Team and patches and a new security release of Drupal core is released within a very short period of time from the time the vulnerability is identified. Once the new security update for the module or Drupal core is released it would be the responsibility of each Drupal site owner (development team) to keep their site updated. A general recommendation is to update the site with all the security updates as soon as the security updates are released.");
  }

  /**
   * Return information about plugin according annotation.
   *
   * @return mixed
   *   Associated array.
   */
  public function getInformation() {
    return $this->t('test information');
  }

  /**
   * Return information about plugin according annotation.
   *
   * @return mixed
   *   Associated array.
   */
  public function getTitle() {
    return 'test';
  }

  /**
   * Return information about plugin according annotation.
   *
   * @return mixed
   *   Associated array.
   */
  public function getCategory() {
    return 'test';
  }

  /**
   * Return string with check status.
   *
   * @return string
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function getProcessStatus() {
    return 'fail';
  }

  /**
   * Set check status.
   *
   * @param string $status
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function setProcessStatus($status) {

  }

  /**
   * Return stored from last checking data.
   *
   * @return mixed
   *   array results where every item is associated array with keys:
   *   'point_name', 'severity', 'status', 'description'.
   */
  public function getRecentReport() {
    return [];
  }

  /**
   * Return information about next actions.
   *
   * @return mixed
   *   Associated array.
   */
  public function getActions() {
    if ($this->getProcessStatus() == 'fail') {
      return $this->t('Test');
    }
    return $this->t('No actions to be done.');
  }

  /**
   * Return information about impacts.
   *
   * @return mixed
   *   Associated array.
   */
  public function getImpacts() {
    if ($this->getProcessStatus() == 'fail') {
      return $this->t("If you don’t monitor for new versions and ignore core updates, your application is in danger as hackers follow security-related incidents (which have to be published as soon as they're discovered) and try to exploit the known vulnerabilities. Also each new version of the Drupal core contains bug fixes, which increases the stability of the entire platform.");
    }
    return NULL;
  }

  /**
   * Process checkpoint review.
   */
  public function process() {

    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->getDescription(),
      'information' => $this->getInformation(),
      'status' => $this->getProcessStatus(),
      'severity' => $this->getPluginDefinition()['severity'],
      'actions' => $this->getActions(),
      'impacts' => $this->getImpacts(),
    ];

    \Drupal::logger('test results')->notice('<pre>' . print_r($result, 1) . '</pre>');

    $results[$this->getCategory()][$this->getPluginId()] = $result;
    return $results;
  }

}
