<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Release notes & help files.
 *
 * @AuditPlugin(
 *   id = "release_notes_help_files",
 *   label = @Translation("Release notes & help files"),
 *   category = "server_configuration",
 *   requirements = {},
 *   enabled = true,
 *   severity = "critical"
 * )
 */
class ServerHelpFilesPlugin extends AuditBasePlugin implements PluginFormInterface {

  use AuditPluginSubform;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form['files'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Files for checking'),
      '#description' => $this->t('Place one filepath per line as relative without preceding slash. i.e path/to/file.'),
      '#default_value' => $settings['files'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {

    $settings = $this->getSettings();
    $config_files = $this->parseLines($settings['files']);

    $remaining_files = [];
    foreach ($config_files as $file) {
      if (file_exists(DRUPAL_ROOT . '/' . $file)) {
        array_push($remaining_files, $file);
      }
    }

    if (!empty($remaining_files)) {
      $issues = [];
      foreach ($remaining_files as $remaining_file) {
        $issues[] = [
          '@issue_title' => 'File on server: @file',
          '@file' => $remaining_file,
        ];
      }
      return $this->fail(NULL, ['issues' => $issues]);
    }

    return $this->success();
  }

}
