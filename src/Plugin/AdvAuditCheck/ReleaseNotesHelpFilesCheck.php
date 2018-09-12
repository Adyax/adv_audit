<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Release notes & help files.
 *
 * @AdvAuditCheck(
 *   id = "release_notes_help_files",
 *   label = @Translation("Release notes & help files"),
 *   category = "server_configuration",
 *   requirements = {},
 *   enabled = true,
 *   severity = "critical"
 * )
 */
class ReleaseNotesHelpFilesCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, PluginFormInterface {

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
    $params = [];
    $settings = $this->getSettings();
    $config_files = $this->parseLines($settings['files']);
    $config_files = !empty($config_files) ? $config_files : self::DEFAULT_FILES;

    $remaining_files = [];
    foreach ($config_files as $file) {
      if (file_exists(DRUPAL_ROOT . '/' . $file)) {
        array_push($remaining_files, $file);
      }
    }

    if (!empty($remaining_files)) {
      $params['remaining_files'] = $remaining_files;
      return $this->fail($this->t('There are number of help/release notes files left.'), $params);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type != AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      return [];
    }

    $issue_details = $reason->getArguments();
    if (empty($issue_details['remaining_files'])) {
      return [];
    }

    return [
      '#type' => 'container',
      'msg' => [
        '#markup' => $this->t('Release note & help files still present on your server.'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => $issue_details['remaining_files'],
      ],
    ];
  }

}
