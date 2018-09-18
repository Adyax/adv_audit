<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Check files structure on project.
 *
 * @AuditPlugin(
 *   id = "files_structure_check",
 *   label = @Translation("Check files structure on project."),
 *   category = "architecture_analysis",
 *   requirements = {},
 * )
 */
class ArchitectureFilesStructurePlugin extends AuditBasePlugin implements ContainerFactoryPluginInterface {

  /**
   * Kernel container.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  protected $issues;

  const MODULES_BASE = 'modules';

  const THEMES_BASE = 'themes';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DrupalKernel $kernel) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->kernel = $kernel;
    $this->issues = [];
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
   * {@inheritdoc}
   */
  public function perform() {

    $site_dir = $this->kernel->getSitePath();
    $scan_dirs = [
      static::MODULES_BASE,
      static::THEMES_BASE,
      $site_dir . '/modules',
      $site_dir . '/themes',
    ];

    foreach ($scan_dirs as $dir) {
      $this->scanFolder($dir);
    }

    if (!empty($this->issues)) {
      return $this->fail(NULL, [
        'issues' => $this->issues,
        '%link' => Link::fromTextAndUrl($this->t('Directory Structure'),
          Url::fromUri('https://www.drupal.org/docs/8/understanding-drupal/directory-structure'))
          ->toString(),
      ]);
    }
    return $this->success();
  }

  /**
   * Check if project's modules folder contain contrib or custom folders.
   *
   * And then scan issues.
   */
  protected function scanFolder($path) {

    $this->issues[$path . 'no_contrib'] = [
      '@issue_title' => 'Folder contrib does not exist in @path',
      '@path' => $path,
    ];

    $this->issues[$path . 'no_custom'] = [
      '@issue_title' => 'Folder custom does not exist in @path',
      '@path' => $path,
    ];

    $modules_dir_list = is_dir($path) ? scandir($path) : FALSE;

    // Skip if path not exists.
    if (!$modules_dir_list) {
      unset($this->issues[$path . 'no_custom']);
      unset($this->issues[$path . 'no_contrib']);
      return;
    }

    foreach ($modules_dir_list as $dir) {
      if (($dir !== '.' && $dir !== '..') && is_dir(DRUPAL_ROOT . '/' . $path . '/' . $dir)) {

        if ($dir === 'contrib') {
          unset($this->issues[$path . 'no_contrib']);
        }
        if ($dir === 'custom') {
          unset($this->issues[$path . 'no_custom']);
        }

        $this->scanIssues($path, $dir);
      }
    }
  }

  /**
   * Check project's "modules" or "themes" folders.
   *
   * It is an issue if there are modules or themes installed directly
   * in this folder.
   */
  public function scanIssues($path, $dir) {
    $internal_dirs = scandir(DRUPAL_ROOT . '/' . $path . '/' . $dir);
    $needle = '.info.yml';
    foreach ($internal_dirs as $item) {

      if (strpos($item, $needle)) {
        $this->issues[$path . $dir] = [
          '@issue_title' => 'Wrong structure in folder @path (@dir_name)',
          '@path' => $path,
          '@dir_name' => $dir,
        ];
      }
    }
  }

}
