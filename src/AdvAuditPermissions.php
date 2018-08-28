<?php

namespace Drupal\adv_audit;

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

  /**
   * Constructs a new AdvAuditPermissions object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Use DI to work with config.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Returns an array of permissions.
   */
  public function auditPermissions() {
    $audit_config = $this->config->get('adv_audit.config');
    $categories = $audit_config->get('adv_audit_settings.categories');

    $perms = [];
    // Generate permissions for all categories and plugins.
    foreach ($categories as $category => $definition) {
      $perms["adv_audit category $category edit"] = [
        'title' => $this->t('Edit %name category settings', ['%name' => $definition['label']]),
      ];
    }

    return $perms;
  }

}