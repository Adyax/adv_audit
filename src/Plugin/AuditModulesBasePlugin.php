<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Base class for Advances audit modules updates check plugins.
 */
abstract class AuditModulesBasePlugin extends AuditBasePlugin {

  /**
   * Store modules list.
   *
   * @var array
   *   Updates list.
   */
  protected $updates = [];

  /**
   * Number of updates.
   *
   * @var mixed
   *   Modules count.
   */
  protected $count;

  /**
   * Drupal\update\UpdateManagerInterface definition.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $updateManager;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Defines whether to check For Security updates or not.
   *
   * @var bool
   */
  const CHECK_FOR_SECURITY_UPDATES = FALSE;

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $projects = update_get_available(TRUE);
    $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');
    $projects = update_calculate_project_data($projects);

    $manager = $this->updateManager;

    foreach ($projects as $project) {
      if ($project['status'] == $manager::CURRENT || $project['project_type'] != 'module') {
        continue;
      }

      if (!static::CHECK_FOR_SECURITY_UPDATES && !empty($project['security updates'])) {
        continue;
      }
      if (static::CHECK_FOR_SECURITY_UPDATES && empty($project['security updates'])) {
        continue;
      }

      // Replace title with name property if title doesn't exist.
      $project['title'] = isset($project['title']) ? $project['title'] : $project['info']['name'];

      // Exclude module from list if recommended version not exist's.
      if (isset($project['recommended'])) {
        $this->updates[] = [
          'label' => !empty($project['link']) ? Link::fromTextAndUrl($project['title'], Url::fromUri($project['link'])) : $project['title'],
          'current_v' => $project['existing_version'],
          'recommended_v' => $project['recommended'],
        ];
      }

    }

    if (!empty($this->updates)) {
      $issues = [];
      foreach ($this->updates as $item) {
        $issues[] = [
          '@issue_title' => "Module's \"@label\" current version is @current_v. Recommended: @recommended_v",
          '@label' => is_string($item['label']) ? $item['label'] : $item['label']->getText(),
          '@current_v' => $item['current_v'],
          '@recommended_v' => $item['recommended_v'],
        ];
      }

      return $this->fail(NULL, ['issues' => $issues]);
    }

    return $this->success();
  }

}
