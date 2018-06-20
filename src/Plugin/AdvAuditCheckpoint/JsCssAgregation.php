<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
class JsCssAgregation extends AdvAuditCheckpointBase {

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
  public function getTitle() {
    return 'Javascript/CSS aggregation and compression';
  }

  /**
   * Return information about next actions.
   *
   * @return mixed
   *   Associated array.
   */
  public function getActions() {
    if ($this->getProcessStatus() == 'fail') {
      $link = Link::fromTextAndUrl('Advanced CSS/JS Aggregation', Url::fromUri('https://www.drupal.org/project/advagg'));
      return $this->t('Enable core aggregation or use @link (that includes all latest security updates).', ['@link' => $link->toString()]);
    }
    return $this->t('No actions needed.');
  }

  /**
   * Return information about impacts.
   *
   * @return mixed
   *   Associated array.
   */
  public function getImpacts() {
    return $this->t("If you don’t monitor for new versions and ignore core updates, your application is in danger as hackers follow security-related incidents (which have to be published as soon as they're discovered) and try to exploit the known vulnerabilities. Also each new version of the Drupal core contains bug fixes, which increases the stability of the entire platform.");
  }

  /**
   * Return information about plugin according annotation.
   *
   * @return mixed
   *   Associated array.
   */
  public function getInformation() {
    $link = Link::fromTextAndUrl('CSS/JS Aggregation', Url::fromRoute('system.performance_settings'));
    if ($this->getProcessStatus() == 'fail') {
      return $this->t('Your %link settings are disabled, they should be enabled on a production environment! This should not cause trouble if you steer clear of @import statements.', ['%link' => $link->toString()]);
    }
    return $this->t('Your %link settings are OK for production use.', ['%link' => $link->toString()]);
  }

  /**
   * Process checkpoint review.
   */
  public function process() {
    $css_preprocess = $this->configFactory->get('system.performance')->get('css.preprocess');
    $js_preprocess = $this->configFactory->get('system.performance')->get('js.preprocess');

    if (!$css_preprocess || !$js_preprocess) {
      $this->setProcessStatus('fail');
    }
    else {
      $this->setProcessStatus('success');
    }

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

    \Drupal::logger('test results')
      ->notice('<pre>' . print_r($result, 1) . '</pre>');

    $results[$this->getCategory()][$this->getPluginId()] = $result;
    return $results;
  }

}
