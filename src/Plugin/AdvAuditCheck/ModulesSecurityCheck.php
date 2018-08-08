<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Renderer;

/**
 * @AdvAuditCheck(
 *   id = "modules_security_check",
 *   label = @Translation("Modules security updates"),
 *   category = "core_and_modules",
 *   requirements = {},
 *   enabled = TRUE,
 *   severity = "critical"
 * )
 */
class ModulesSecurityCheck extends AdvAuditCheckBase implements  AdvAuditCheckInterface, ContainerFactoryPluginInterface {

  /**
   * Store modules list.
   *
   * @var array
   *   Security updates list.
   */
  protected $securityUpdates = [];

  /**
   * Number of security updates.
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
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UpdateManagerInterface $update_manager, ModuleHandlerInterface $module_handler, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->updateManager = $update_manager;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
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
      $container->get('module_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
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
        $this->securityUpdates[] = [
          'label' => Link::fromTextAndUrl($project['title'], Url::fromUri($project['link'])),
          'current_v' => $project['existing_version'],
          'recommended_v' => $project['recommended'] || $project['latest_version'],
        ];
      }
    }

    $link = Link::fromTextAndUrl('There', Url::fromRoute('update.module_update'));
    if (!empty($this->securityUpdates)) {
      $render_list = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Installed version'),
          $this->t('Recommended version'),
        ],
        '#rows' => $this->securityUpdates,
      ];
    }
    else {
      $render_list = [];
    }

    $params = [
      '@link' => $link->toString(),
      '@count' => $this->count,
      '@list' => $this->renderer->render($render_list),
    ];

    return new AuditReason($this->id(), $status, $reason, $params);
  }


}
