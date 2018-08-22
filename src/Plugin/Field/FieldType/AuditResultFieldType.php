<?php

namespace Drupal\adv_audit\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;

/**
 * Plugin implementation of the 'audit_result' field type.
 *
 * @FieldType(
 *   id = "audit_result",
 *   label = @Translation("Audit result field type"),
 *   description = @Translation("Adv Audit result"),
 *   default_widget = "audit_report_widget",
 *   default_formatter = "audit_report_formatter"
 * )
 */
class AuditResultFieldType extends MapItem {

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    if (isset($value) && !empty($value)) {
      $this->values[$name] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    if (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue() !== NULL;
    }
    return $this->values->$name;
  }

}
