<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if there is any DB update required.
 *
 * @AdvAuditCheck(
 *   id = "database_update_required",
 *   label = @Translation("No database updates required"),
 *   category = "core_and_modules",
 *   severity = "critical",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class DatabaseUpdateRequired extends AdvAuditCheckBase implements AdvAuditCheckInterface, ContainerFactoryPluginInterface {

  /**
   * System Manager Container.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * Constructs Database Update Required Check.
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    SystemManager $system_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->systemManager = $system_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('system.manager')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $requirements = $this->systemManager->listRequirements();
    if (empty($requirements['update']['severity'])) {
      return $this->fail(NULL, [
        'issues' => [
          'database_update_required' => [
            '@issue_title' => 'Database needs to be updated.',
          ],
        ],
      ]);
    }

    return $this->success();
  }

}
