<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Checks Ultimate cron module enabled.
 *
 * @AuditPlugin(
 *   id = "ultimate_cron",
 *   label = @Translation("Check Ultimate cron module"),
 *   category = "other",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class OtherUltimateCronPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    if (!$this->moduleHandler->moduleExists('ultimate_cron')) {
      return $this->fail(NULL, [
        'issues' => [
          'ultimate_cron' => [
            '@issue_title' => 'Module Ultimate cron is not installed.'
          ],
        ],
      ]);
    }

    return $this->success();
  }

}
