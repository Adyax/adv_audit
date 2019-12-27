<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use SensioLabs\Security\SecurityChecker;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Check code for security issues.
 *
 * @AuditPlugin(
 *  id = "security_code_review",
 *  label = @Translation("Security Code Review"),
 *  category = "security",
 *  requirements = {},
 * )
 */
class SecurityCodeReviewPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  /**
   * Store plugin issues.
   *
   * @var mixed
   */
  protected $issues;

  /**
   * The DrupalKernel class is the core of Drupal itself.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * The Drupal Core render service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $render;

  /**
   * SensioLabs check tools.
   *
   * @var \SensioLabs\Security\SecurityChecker
   */

  protected $composerCheck;

  /**
   * Constructs a new PerformanceViewsPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\DrupalKernel $kernel
   *   Drupal kernel service.
   * @param \Drupal\Core\Render\Renderer $render
   *   Drupal render service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DrupalKernel $kernel, Renderer $render) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->composerCheck = new SecurityChecker();
    $this->kernel = $kernel;
    $this->render = $render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('kernel'),
      $container->get('renderer')
    );
  }

  /**
   * Check vendor vulnerabilities with sensioLabs checker.
   */
  protected function checkComposerDependencies() {
    $app_root = $this->kernel->getAppRoot();
    $composer_lock = is_file($app_root . '/composer.lock') ? $app_root . '/composer.lock' : $app_root . '/../composer.lock';
    if (is_file($composer_lock)) {
      $check_result = $this->composerCheck->check($composer_lock);
      $vulnerabilities = $check_result->__toString();
      $format = $check_result->getFormat();
      if ($format == 'json') {
        $this->issues = json_decode($vulnerabilities);
      }
      return;
    }
    $this->issues = [];
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $this->checkComposerDependencies();
    if (count($this->issues)) {
      return $this->fail(NULL, [
        'issues' => $this->getIssues(),
        '%link' => Link::fromTextAndUrl($this->t('Check your PHP project for known security issues!'),
          Url::fromUri('https://security.sensiolabs.org'))
          ->toString(),
      ]);
    }
    return $this->success();
  }

  /**
   * Prepare issues list.
   *
   * @return array
   *   Issues ready to
   */
  protected function getIssues() {
    $result = [];
    foreach ($this->issues as $name => $value) {
      if (isset($value->advisories)) {
        foreach ($value->advisories as $key => $item) {
          $result[$name . ' : ' . $key] = [
            '@issue_title' => 'Title @title; description @url',
            '@version' => isset($item->cve) ? $item->cve : '',
            '@url' => isset($item->link) ? $item->link : '',
            '@title' => isset($item->title) ? $item->title : '',
          ];
        }
      }
    }
    return $result;
  }

}
