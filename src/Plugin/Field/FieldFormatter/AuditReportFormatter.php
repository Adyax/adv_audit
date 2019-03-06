<?php

namespace Drupal\adv_audit\Plugin\Field\FieldFormatter;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponse;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'audit_report_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "audit_report_formatter",
 *   label = @Translation("Audit report formatter"),
 *   field_types = {
 *     "audit_result"
 *   }
 * )
 */
class AuditReportFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if ($entity_type !== 'adv_audit_issue') {
      foreach ($items as $delta => $item) {
        $view_mode = $this->viewMode;
        $adv_audit_id = $entity->id->value;
        $elements[$delta]['#theme'] = ['adv_audit_report__' . $view_mode];
        $elements[$delta]['#view_mode'] = $view_mode;
        $elements[$delta]['#adv_audit_id'] = $adv_audit_id;
        $elements[$delta]['#report'] = $this->getResultObject($item);
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function getResultObject(FieldItemInterface $item) {
    $value = $item->getValue();
    if (!($value instanceof AuditResultResponse)) {
      /** @var AuditResultResponse $audit_result */
      $audit_result = new AuditResultResponse();
      if (!empty($value['results'])) {
        foreach ($value['results'] as $result) {
          $plugin_id = $result['testId'];
          $status = $result['status'];
          $reason = $result['reason'];
          $arguments = $result['arguments'];
          $issues = $result['issues'];
          $reason = new AuditReason($plugin_id, $status, $reason, $arguments);
          $reason->setIssues($issues);
          $audit_result->addReason($reason, false);
        }
      }
      if (!empty($value['overviewInfo'])) {
        $audit_result->setOverviewInfo($value['overviewInfo']);
      }
      return $audit_result;
    }

    return $value;
  }

}
