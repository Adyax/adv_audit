<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use SensioLabs\Security\SecurityChecker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check code for security issues.
 *
 * @AdvAuditCheck(
 *  id = "security_code_review",
 *  label = @Translation("Security Code Review"),
 *  category = "security",
 *  severity = "critical",
 *  requirements = {},
 *  enabled = true,
 * )
 */
class SecurityCodeReview extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * The DrupalKernel class is the core of Drupal itself.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * SensioLabs check tools.
   *
   * @var \SensioLabs\Security\SecurityChecker
   */

  protected $composerCheck;

  /**
   * Constructs a new PerformanceViewsCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\DrupalKernel $kernel
   *   Drupal kernel service..
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DrupalKernel $kernel) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->composerCheck = new SecurityChecker();
    $this->kernel = $kernel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('kernel')
    );
  }

  /**
   * Check vendor vulnerabilities with sensioLabs checker.
   */
  protected function checkComposerDependencies() {
    $app_root = $this->kernel->getAppRoot();
    $composer_lock = is_file($app_root . '/composer.lock') ? $app_root . '/composer.lock' : $app_root . '/../composer.lock';
    if (is_file($composer_lock)) {
      return $this->composerCheck->check($composer_lock);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $issues = $this->checkComposerDependencies();
    if (count($issues)) {
      return $this->fail(NULL, $issues);
    }
    return $this->success();
  }

}
