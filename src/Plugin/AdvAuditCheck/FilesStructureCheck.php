<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check files structure on project.
 *
 * @AdvAuditCheck(
 *   id = "files_structure_check",
 *   label = @Translation("Check files structure on project."),
 *   category = "architecture_analysis",
 *   severity = "normal",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class FilesStructureCheck extends AdvAuditCheckBase implements ContainerFactoryPluginInterface {

  /**
   * Kernel container.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DrupalKernel $kernel) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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

  const MODULES_BASE = DRUPAL_ROOT . '/modules';

  const THEMES_BASE = DRUPAL_ROOT . '/themes';

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $issue_details = [];
    $modules_in_base_failed = $this->scanFolder(self::MODULES_BASE);
    $themes_in_base_failed = $this->scanFolder(self::THEMES_BASE);
    $sites_folder_failed = $this->scanSitesFolder();

    if (!empty($modules_in_base_failed)) {
      $issue_details['modules_in_base'] = $modules_in_base_failed;
    }
    if (!empty($themes_in_base_failed)) {
      $issue_details['themes_in_base'] = $themes_in_base_failed;
    }
    if (!empty($sites_folder_failed)) {
      $issue_details[$this->kernel->getSitePath()] = $this->scanSitesFolder();
    }

    if (!empty($issue_details)) {
      return $this->fail($this->t('There are some issues'), $issue_details);
    }
    return $this->success();
  }

  /**
   * Check if project's modules folder doesn't contain modules folders directly.
   *
   * @return array
   *   Modules which are exist in "modules" folder directly.
   */
  protected function scanFolder($path) {
    $folders = [];
    $modules_dir_list = scandir($path);

    $needle = '.info.yml';
    foreach ($modules_dir_list as $dir) {
      if (($dir !== '.' && $dir !== '..') && is_dir($path . '/' . $dir)) {

        if ($dir === 'contrib') {
          $folders['contrib_exists'] = TRUE;
        }

        $internal_dirs = scandir($path . '/' . $dir);
        foreach ($internal_dirs as $item) {

          if (strpos($item, $needle)) {
            $folders[] = $dir;
          }

        }
      }
    }
    return $folders;
  }

  /**
   * Check in site folder in {docroot}/sites/{sitefolder}.
   *
   * If folders "modules" or "themes" are exist scan them.
   *
   * @return array
   *   List of fails if they are exist.
   */
  protected function scanSitesFolder() {
    $folders = [];
    $site_path = $this->kernel->getSitePath();
    $site_dirs = scandir(DRUPAL_ROOT . '/' . $site_path);

    foreach ($site_dirs as $dir) {
      if ($dir === 'modules') {
        $sites_modules = $this->scanFolder(DRUPAL_ROOT . '/' . $site_path . '/' . $dir);
        $folders[$dir] = $sites_modules;
      }
      if ($dir === 'themes') {
        $sites_themes = $this->scanFolder(DRUPAL_ROOT . '/' . $site_path . '/' . $dir);
        $folders[$dir] = $sites_themes;
      }
    }

    return $folders;
  }

  /**
   * Get items for auditReportRender if base files structure failed.
   *
   * @param array $list
   *   List of failed modules.
   * @param string $folder
   *   Folder in which test was failed.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   TranslatableMarkup.
   */
  protected function getItems(array $list, $folder): TranslatableMarkup {
    foreach ($list as $key => $value) {
      if ($key === 'contrib_exists') {
        $contrib = TRUE;
        break;
      }
    }
    $placeholder = ['%folder' => $folder];
    return $contrib ?
      $this->t('Base "%folder" folder contains %folder (contrib folder exists):', $placeholder) :
      $this->t("Base \"%folder\" folder contains %folder (contrib folder doesn't exists):", $placeholder);
  }

  /**
   * Get item if files structure failed in sites folder for multisite.
   *
   * @param array $list
   *   List of failed sites.
   *
   * @return array
   *   List of failed sites.
   */
  protected function getItemsMultiSitesFailed(array $list) {
    foreach ($list as $key => $value) {
      $sites_list[] = $key;
    }
    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Multisite folders:'),
      '#list_type' => 'ul',
      '#items' => $sites_list,
    ];
  }

}
