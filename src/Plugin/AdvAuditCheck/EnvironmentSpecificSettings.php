<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;

/**
 * Check environment settings.
 *
 * @AdvAuditCheck(
 *   id = "environment_specific_settings",
 *   label = @Translation("Check environment settings."),
 *   category = "other",
 *   severity = "normal",
 *   requirements = {},
 *   enabled = TRUE,
 * )
 */
class EnvironmentSpecificSettings extends AdvAuditCheckBase {


  protected $issues = [];

  /**
   * Define the path to environments folder.
   *
   * @var string
   */
  protected $envFolder = DRUPAL_ROOT . '/../environments/';

  /**
   * Define a list of specific environments folders.
   *
   * @var array
   */
  protected $envs = [
    'dev',
    'docker',
    'local',
    'preprod',
    'prod',
    'stage',
  ];

  /**
   * {@inheritdoc}
   */
  public function perform() {

    $this->scanEnvFolder();

    if (!empty($this->issues)) {
      return $this->fail(NULL, ['issues' => $this->issues]);
    }
    return $this->success();
  }

  /**
   * Scans "environments" folder.
   *
   * There are issue if one of the $this->envs doesn't exists.
   */
  protected function scanEnvFolder() {
    if (file_exists($this->envFolder) && is_dir($this->envFolder)) {
      $env_dirs = scandir($this->envFolder);
      foreach ($this->envs as $env_dir) {
        if (!in_array($env_dir, $env_dirs)) {
          $this->issues[$env_dir . '_no_folder'] = [
            '@issue_title' => 'Folder "@env_dir" does not exist',
            '@env_dir' => $env_dir,
          ];
        }
        if (is_dir($this->envFolder . $env_dir)) {
          $this->scanEnvSettings($env_dir);
        }
      }
    }
    else {
      $this->issues['no_env_folder'] = [
        '@issue_title' => 'Folder "environments" does not exist',
      ];
    }

  }

  /**
   * Check if settings.php is exists in $dir.
   *
   * It is an issue if $dir doesn't contain settings.php.
   */
  public function scanEnvSettings($dir) {
    if (!is_file($this->envFolder . $dir . '/settings.php')) {
      $this->issues[$dir . '_no_settings'] = [
        '@issue_title' => 'File "settings.php" does not exists in folder "@dir"',
        '@dir' => $dir,
      ];
    }
  }

}
