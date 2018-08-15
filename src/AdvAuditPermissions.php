<?php

namespace Drupal\adv_audit;

use Drupal\adv_audit\Plugin\AdvAuditCheckManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for the adv_audit module.
 */
class AdvAuditPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  protected $config;

  protected $auditPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdvAuditCheckManager $advAuditCheckListManager) {
    $this->config = $config_factory;
    $this->auditPluginManager = $advAuditCheckListManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.adv_audit_check')
    );
  }

  /**
   * Returns an array of permissions.
   */
  public function auditPermissions() {
    $audit_config = $this->config->get('adv_audit.config');
    $categories = $audit_config->get('adv_audit_settings.categories');
    $plugin_list = $this->auditPluginManager->getPluginsByCategory();

    $perms = [];
    // Generate audit permissions for all categories and plugins.
    foreach ($categories as $category => $definition) {
      $perms += $this->buildPermissions('category', $category, $definition['label']);

      $plugins = $plugin_list[$category];

      foreach ($plugins as $plugin) {
        $perms += $this->buildPermissions('plugin', $plugin['id'], $plugin['label']);
      }
    }

    return $perms;
  }

  /**
   * Returns a list of permissions.
   *
   * @param string $type
   * @param string $id
   * @param string $name
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions($type, $id, $name) {
    $params = [
      '%name' => $name,
      '@type' => $type,
    ];

    return [
      "adv_audit $type $id view" => [
        'title' => $this->t('View audit results for @type %name', $params),
      ],
      "adv_audit $type $id edit" => [
        'title' => $this->t('Edit settings for @type %name', $params),
      ],
    ];
  }

}
