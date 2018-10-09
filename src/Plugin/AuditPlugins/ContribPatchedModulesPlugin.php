<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check Contrib module and Core for patches.
 *
 * @AuditPlugin(
 *   id = "patched_modules_check",
 *   label = @Translation("Patched modules."),
 *   category = "core_and_modules",
 *   requirements = {
 *     "module": {
 *      "hacked:2.0-beta",
 *     },
 *   }
 * )
 */
class ContribPatchedModulesPlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * ModuleHandler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ModuleHandler $module_handler, CacheBackendInterface $hacked_cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = $hacked_cache;
    $this->moduleHandler = $module_handler;
    $this->validate();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('cache.hacked')
    );
  }

  /**
   * Check if plugin is available.
   */
  private function validate() {
    if (!$this->getStatus()) {
      return;
    }

    // Disable plugin if hacked isn't installed.
    if (!$this->moduleHandler->moduleExists('hacked')) {
      $this->setPluginStatus(FALSE);
      drupal_set_message($this->t('Install @module to use plugins "Patched modules" and "Patched Drupal core".', [
        '@module' => Link::fromTextAndUrl('Hacked', Url::fromUri('https://www.drupal.org/project/hacked'))
          ->toString(),
      ]), 'error');
    }

    // Disable plugin if report wasn't generated.
    $data = $this->cache->get('hacked:full-report');
    if (!$data || !$data->data) {
      $this->setPluginStatus(FALSE);
      drupal_set_message($this->t('@link report to include plugins "Patched modules" and "Patched Drupal core" into audit.', [
        '@link' => Link::fromTextAndUrl('Generate', Url::fromRoute('hacked.manual_status'))
          ->toString(),
      ]), 'error');
    }

  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $issue_details = [];
    $hacked = $this->cache->get('hacked:full-report');
    $hacked = $hacked->data;
    $issue_details['hacked_modules'] = [];
    foreach ($hacked as $project) {
      if ($project['counts']['different'] != 0 && $project['project_type'] == 'module') {
        $issue_details['hacked_modules'][] = $project;
      }
    }

    if (!empty($issue_details['hacked_modules'])) {
      $issues = [];

      foreach ($issue_details['hacked_modules'] as $hacked_module) {
        $issues[] = [
          '@issue_title' => 'Changed module: @changed_module.',
          '@changed_module' => $hacked_module['title'],
        ];
      }

      return $this->fail('', ['issues' => $issues]);
    }

    return $this->success();
  }

}
