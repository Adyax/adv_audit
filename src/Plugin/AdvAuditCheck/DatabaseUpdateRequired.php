<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
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
    if (isset($requirements['update']['severity'])) {
      return new AuditReason(
        $this->id(),
        AuditResultResponseInterface::RESULT_FAIL
      );
    }
    return new AuditReason($this->id(), AuditResultResponseInterface::RESULT_PASS);
  }

}
