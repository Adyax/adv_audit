<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\hacked\Controller\HackedController;

/**
 * Check Contrib module and Core for patches.
 *
 * @AdvAuditCheck(
 *   id = "patched_modules_check",
 *   label = @Translation("Patched modules."),
 *   category = "core_and_modules",
 *   requirements = {
 *     "module": {
 *      "hacked:2.0-beta",
 *     },
 *   },
 *   enabled = true,
 *   severity = "high"
 * )
 */
class PatchedModulesCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, AdvAuditCheckInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Process checkpoint review.
   */
  public function perform() {
    $issue_details = [];
    $hacked = new HackedController();
    $hacked = $hacked->hackedStatus();

    $issue_details['hacked_modules'] = [];
    foreach ($hacked[self::DATA_KEY] as $project) {
      if ($project['counts']['different'] != 0 && $project['project_type'] == 'module') {
        $issue_details['hacked_modules'][] = $project;
      }
    }

    if (!empty($issue_details['hacked_modules'])) {
      return $this->fail(NULL, $issue_details);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();

    $hacked = new HackedController();
    $hacked = $hacked->hackedStatus();
    $is_validated = is_array($hacked) && isset($hacked[self::DATA_KEY]);

    if (!$is_validated) {
      $link = Link::createFromRoute('here', 'hacked.report')->toString();
      throw new RequirementsException(
        $this->t('Hacked report is not generated. You can generate it %link', ['%link' => $link]),
        $this->pluginDefinition['requirements']['module']
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type !== AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      return [];
    }

    $arguments = $reason->getArguments();
    if (empty($arguments['hacked_modules'])) {
      return [];
    }

    $build = [
      '#theme' => 'hacked_report',
      '#data' => $arguments['hacked_modules'],
    ];

    return $build;
  }

}
