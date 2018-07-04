<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Check security updates for contrib modules.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "modules_security_check",
 *   label = @Translation("Modules security updates"),
 *   description = @Translation("Once the new security update for the module is
 *   released it would be the responsibility of each Drupal site owner to keep
 *   their site updated.."),
 *   category = "core_and_modules",
 *   status = TRUE,
 *   severity = "critical"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class ModulesSecurityCheck extends AdvAuditCheckpointBase {

  /**
   * {@inheritdoc}
   */
  public $actionMessage = '@link are @count modules with security updates. These modules must be updated as soon as possible. @list';

  /**
   * {@inheritdoc}
   */
  public $impactMessage = 'If you don’t monitor for new versions and ignore contrib updates, your application is in danger as hackers follow security-related incidents (which have to be published as soon as they\'re discovered) and try to exploit the known vulnerabilities. So it’s very important to keep application fully updated especially when exists security updates and stable versions of contrib modules.';

  /**
   * {@inheritdoc}
   */
  public $failMessage = 'There are outdated modules with security updates.';

  /**
   * Store modules list.
   *
   * @var array
   *   Security updates list.
   */
  protected $securityUpdates = [];

  /**
   * Store implementation of update.manager service.
   *
   * @var \Drupal\update\UpdateManagerInterface
   *   Update manager object.
   */
  protected $updateManager;

  /**
   * Number security updates.
   *
   * @var mixed
   *   Modules count.
   */
  protected $count;

  protected $additionalServices = ['updateManager' => 'update.manager'];

  /**
   * Return description of current checkpoint.
   *
   * @return mixed
   *   Associated array.
   */
  public function getDescription($params = []) {
    return $this->t("Drupal core and contrib modules code are constantly improving. Every release includes  fixed bugs (including security vulnerabilities), improve performance and provide new functionality. It is recommended to update core and contrib modules. (be aware that some contributed modules could be patched). Once the new security update for the module or Drupal core is released it would be the responsibility of each Drupal site owner to keep their site updated.");
  }

  /**
   * Return information about next actions.
   *
   * @param mixed $params
   *   Placeholders for message.
   *
   * @return mixed
   *   Action messages.
   *
   * @throws \Exception
   *   Exception.
   */
  public function getActions($params = []) {

    if (!$params) {
      $link = Link::fromTextAndUrl('There', Url::fromRoute('update.module_update'));
      $render_list = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Installed version'),
          $this->t('Recommended version'),
        ],
        '#rows' => $this->securityUpdates,
      ];
      $params = [
        '@link' => $link->toString(),
        '@count' => $this->count,
        '@list' => $this->renderer->render($render_list),
      ];
    }

    return parent::getActions($params);
  }

  /**
   * Process checkpoint review.
   */
  public function process() {
    $this->count = 0;
    $projects = update_get_available(TRUE);
    $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');
    $projects = update_calculate_project_data($projects);

    foreach ($projects as $project) {
      if ($project['status'] == $this->updateManager::CURRENT || $project['project_type'] != 'module') {
        continue;
      }

      if (isset($project['security updates']) && $project['security updates']) {
        $this->setProcessStatus('fail');
        $this->count += 1;
        $this->securityUpdates[] = [
          'label' => Link::fromTextAndUrl($project['title'], Url::fromUri($project['link'])),
          'current_v' => $project['existing_version'],
          'recommended_v' => $project['recommended'] || $project['latest_version'],
        ];
      }
    }

    // Collect check results.
    $result = [
      'title' => $this->getTitle(),
      'description' => $this->getDescription(),
      'information' => $this->getProcessResult(),
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
  public function help() {
    return $this->t('Check security updates for modules.');
  }

}
