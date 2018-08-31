<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
class FilesStructureCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface {

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

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      $folders = $reason->getArguments();
      $items = [];

      foreach ($folders as $folder => $list) {
        if ($folder === 'modules_in_base') {
          $items[] = $this->getItem($list, 'modules');
        }

        if ($folder === 'themes_in_base') {
          $items[] = $this->getItem($list, 'themes');
        }

        if ($folder === 'multisites') {
          $items[] = $this->getMultiSites($list);
        }
      }
      $build['folders_fail'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Failed folders'),
        '#list_type' => 'ul',
        '#items' => $items,
      ];
      return $build;
    }
    return [];
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
  protected function getItem(array $list, $folder): TranslatableMarkup {
    foreach ($list as $key => $value) {
      if ($key === 'contrib_exists') {
        $base_contrib = 'contrib folder exists';
        break;
      }
    }
    $placeholder = ['%folder' => $folder];
    return $base_contrib ?
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
  protected function getMultiSites(array $list) {
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
