<?php

namespace Drupal\adv_audit\Plugin\Field\FieldFormatter;

use Drupal\adv_audit\AuditResultResponse;
use Drupal\Component\Utility\Html;
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
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'adv_audit_html_results',
        '#audit_result' => $this->getResultObject($item)
      ];
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
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    $value = $item->getValue();
    $value = isset($value['value']) ? $value['value'] : NULL;

    if (!($value instanceof AuditResultResponse)) {
      return NULL;
    }

    return $value;
  }

}
