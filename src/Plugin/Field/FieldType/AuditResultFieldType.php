<?php

namespace Drupal\adv_audit\Plugin\Field\FieldType;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponse;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;

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
class AuditResultFieldType extends StringLongItem {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    // Update the values and return them.
    foreach ($this->properties as $name => $property) {
      $definition = $property->getDataDefinition();
      if (!$definition->isComputed()) {
        $value = $property->getValue();
        // Only write NULL values if the whole map is not NULL.
        if (isset($this->values) || isset($value)) {
          $this->values[$name] = unserialize($value);
        }
      }
    }
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $response = new AuditResultResponse();
    $response->addReason(new AuditReason('dummy', rand(0,2)));
    $values['value'] = serialize($response);
    return $values;
  }
}
