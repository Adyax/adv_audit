<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheckpoint;

use Drupal\adv_audit\Plugin\AdvAuditCheckpointBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Check non-security updates for contrib modules.
 *
 * @AdvAuditCheckpointAnnotation(
 *   id = "modules_update_check",
 *   label = @Translation("Modules non-security updates"),
 *   description = @Translation("Check non-security module updates."),
 *   category = "core_and_modules",
 *   status = TRUE,
 *   severity = "high"
 * )
 *
 * @package Drupal\adv_audit\Plugin\AdvAuditCheckpoint
 */
class ModulesUpdateCheck extends AdvAuditCheckpointBase {

  /**
   * {@inheritdoc}
   */
  public $actionMessage = '@link are @count with non-security updates. Apply all updates (we recommend apply only stable releases). @list';

  /**
   * {@inheritdoc}
   */
  public $impactMessage = 'Ignoring non-security (normal) updates may lead to compatibility issues between modules in a project. Let\'s imagine you had to update a single module because of the critical security flaw in it. However, as you haven\'t updated it for a while, as well as other modules in a project, a maintainer introduced a few API changes since then. So now, when you apply the security update, you will immediately face the compatibility problem.';

  /**
   * {@inheritdoc}
   */
  public $failMessage = 'There are outdated modules with non-security updates.';

  /**
   * Store modules list.
   *
   * @var array
   *   Security updates list.
   */
  protected $moduleUpdates = [];

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

  protected $additionalServices = [
    'updateManager' => 'update.manager',
    'stringTranslation' => 'string_translation',
  ];

  /**
   * Return description of current checkpoint.
   *
   * @return mixed
   *   Associated array.
   */
  public function getDescription($params = []) {
    return $this->t("The contributed modules should be last stable version as there might a new feature upgrade, some new functionality and bug fixes. It's important to keep modules up-to-date and prevent any problems with compatibility of code (php version, and with other modules) and bugs. Also it’s important to update modules to stable releases (if exists).");
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
        '#rows' => $this->moduleUpdates,
      ];
      $params = [
        '@link' => $link->toString(),
        '@count' => $this->stringTranslation->formatPlural($this->count, '1 module', '@count modules'),
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

      if (!isset($project['security updates'])) {
        $this->setProcessStatus('fail');
        $this->count += 1;
        $this->moduleUpdates[] = [
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
    return $this->t('Check non-security updates for modules.');
  }

}
