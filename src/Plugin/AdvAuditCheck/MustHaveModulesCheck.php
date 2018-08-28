<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Check must-have modules for security reasons.
 *
 * @AdvAuditCheck(
 *   id = "must_have_modules",
 *   label = @Translation("Check must-have modules for security reasons"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 *  )
 */
class MustHaveModulesCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * List of modules to check.
   */
  const SECURITY_MODULES = [
    'captcha',
    'honeypot',
    'password_policy',
    'username_enumeration_prevention',
  ];

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
   * Process checkpoint review.
   */
  public function perform() {
    $enabled_modules = [];
    foreach (self::SECURITY_MODULES as $module_name) {
      if ($this->moduleHandler->moduleExists($module_name)) {
        $enabled_modules[] = $module_name;
      }
    }

    $diff = array_values(array_diff(self::SECURITY_MODULES, $enabled_modules));
    if (!empty($diff) && $diff != ['captcha'] && $diff != ['honeypot']) {
      return $this->fail($this->t('One or more recommended modules are not installed.'), ['@disabled_modules' => implode(', ', $diff)]);
    }

    return $this->success();
  }

}
