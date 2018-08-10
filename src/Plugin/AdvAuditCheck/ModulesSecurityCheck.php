<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditModulesCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @AdvAuditCheck(
 *   id = "modules_security_check",
 *   label = @Translation("Modules security updates"),
 *   category = "core_and_modules",
 *   requirements = {},
 *   enabled = true,
 *   severity = "critical"
 * )
 */
class ModulesSecurityCheck extends AdvAuditModulesCheckBase implements  AdvAuditCheckInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a new ModulesSecurityCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UpdateManagerInterface $update_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->updateManager = $update_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('update.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform($condition = NULL) {
    $this->count = 0;
    $projects = update_get_available(TRUE);
    $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');
    $projects = update_calculate_project_data($projects);

    $manager = $this->updateManager;
    $status = AuditResultResponseInterface::RESULT_PASS;
    $reason = NULL;

    foreach ($projects as $project) {
      if ($project['status'] == $manager::CURRENT || $project['project_type'] != 'module') {
        continue;
      }

      if (isset($project['security updates']) && $project['security updates']) {
        $status = AuditResultResponseInterface::RESULT_FAIL;
        $reason = $this->t('There are outdated modules with security updates.');
        $this->count += 1;
        $this->updates[] = [
          'label' => Link::fromTextAndUrl($project['title'], Url::fromUri($project['link'])),
          'current_v' => $project['existing_version'],
          'recommended_v' => $project['recommended'] || $project['latest_version'],
        ];
      }
    }

    $link = Link::fromTextAndUrl('There', Url::fromRoute('update.module_update'));

    $params = [
      '@link' => $link,
      '@count' => $this->count,
      '@list' => $this->updates,
    ];

    return new AuditReason($this->id(), $status, $reason, $params);
  }


}
