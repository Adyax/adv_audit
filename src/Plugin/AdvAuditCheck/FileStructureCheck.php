<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

/**
 * Check files structure on project.
 *
 * @AdvAuditCheck(
 *   id = "file_structure_check",
 *   label = @Translation("Check files structure on project."),
 *   category = "architecture_analysis",
 *   severity = "normal",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class FileStructureCheck extends AdvAuditCheckBase {

  const MODULES_BASE = DRUPAL_ROOT . '/modules/';

  const THEMES_BASE = DRUPAL_ROOT . '/themes/';

  const MULTISITE = DRUPAL_ROOT . '/sites/';

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $issue_details = [];

    $issue_details['modules_in_base'] = $this->scanFolder(self::MODULES_BASE);
    $issue_details['themes_in_base'] = $this->scanFolder(self::THEMES_BASE);
    $issue_details['multisites'] = $this->scanMultisiteFolders();

    if (!empty($issue_details)) {
      return $this->fail($this->t('There are some issues'), $issue_details);
    }
    return $this->success();
  }

  /**
   * Check if project's modules folder doesn't contain modules folders directly.
   *
   * @return array
   *   Folders which are exist in modules folder directly.
   */
  protected function scanFolder($path) {
    $folders = [];
    $modules_dir_list = scandir($path);

    $needle = '.info.yml';
    foreach ($modules_dir_list as $dir) {
      if (($dir !== '.' && $dir !== '..') && is_dir($path . $dir)) {

        if ($dir === 'contrib') {
          $folders['contrib_exists'] = TRUE;
        }

        $internal_dirs = scandir($path . $dir);
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
   * Scan multisite's project dirs.
   *
   * @return array
   *   Sites and failed modules and themes lists.
   */
  protected function scanMultisiteFolders() {
    $folders = [];

    $sites_dirs = scandir(self::MULTISITE);

    foreach ($sites_dirs as $site_dir) {
      if (($site_dir !== '.' && $site_dir !== '..') && is_dir(self::MULTISITE . $site_dir)) {
        $site_modules_fails_list = $this->scanFolder(self::MULTISITE . $site_dir . '/modules/');
        if (!empty($site_modules_fails_list)) {
          $folders[$site_dir]['modules_in_base'] = $site_modules_fails_list;
        }
        $site_themes_fails_list = $this->scanFolder(self::MULTISITE . $site_dir . '/themes/');
        if (!empty($site_themes_fails_list)) {
          $folders[$site_dir]['themes_in_base'] = $site_themes_fails_list;
        }
      }
    }

    return $folders;
  }

}
