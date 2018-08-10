<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponseInterface;
use Drupal\adv_audit\Plugin\AdvAuditCheckInterface;
use Drupal\adv_audit\Exception\RequirementsException;
use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\hacked\Controller\HackedController;

/**
 * @AdvAuditCheck(
 *   id = "patched_modules_check",
 *   label = @Translation("Patched modules."),
 *   category = "core_and_modules",
 *   requirements = {
 *     "module" = {
 *      "hacked"
 *     },
 *   },
 *   enabled = true,
 *   severity = "high"
 * )
 */
class PatchedModulesCheck extends AdvAuditCheckBase implements  AdvAuditCheckInterface, ContainerFactoryPluginInterface {

  /**
   * Length of the day in seconds.
   */
  const DATA_KEY = '#data';

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
    $params = [];
    $hacked = new HackedController();
    $hacked = $hacked->hackedStatus();

    $status = AuditResultResponseInterface::RESULT_PASS;
    $reason = NULL;
    $hacked_modules = [];

    foreach ($hacked[self::DATA_KEY] as $project) {
      if ($project['counts']['different'] != 0 && $project['project_type'] == 'module') {
        $status = AuditResultResponseInterface::RESULT_FAIL;
        $hacked_modules[] = $project;
      }
    }

    if ($status == AuditResultResponseInterface::RESULT_FAIL) {
      $params['hacked_modules'] = $hacked_modules;
    }

    return new AuditReason($this->id(), $status, $reason, $params);
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
      $link = Link::fromTextAndUrl('here', Url::fromRoute('hacked.report'));
      throw new RequirementsException($this->t('Hacked report is not generated. You can generate it @link', array('@link' => $link)), $this->pluginDefinition['requirements']['module']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_ACTIONS) {
      $arguments = $reason->getArguments();
      if (empty($arguments)) {
        return [];
      }

      $key = 'hacked_modules';

      if (!empty($arguments[$key])) {
        $build = ['#theme' => 'hacked_report'];
        $build[self::DATA_KEY] = $arguments[$key];
        return $arguments[$key];
      }
    }

    return [];
  }

}
