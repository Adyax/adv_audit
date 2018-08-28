<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;

use Drupal\field\Entity\FieldConfig;

/**
 * Unsafe extensions Check plugin class.
 *
 * @AdvAuditCheck(
 *   id = "unsafe_extensions_check",
 *   label = @Translation("Unsafe extensions"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class UnsafeExtensionsCheck extends AdvAuditCheckBase {

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $fields = [];
    $unsafe_ext = [
      'swf',
      'exe',
      'html',
      'htm',
      'php',
      'phtml',
      'py',
      'js',
      'vb',
      'vbe',
      'vbs',
    ];

    // Check field configuration entities.
    foreach (FieldConfig::loadMultiple() as $entity) {
      $extensions = $entity->getSetting('file_extensions');
      if ($extensions != NULL) {
        $extensions = explode(' ', $extensions);
        $unsafe_exts = array_intersect($extensions, $unsafe_ext);
        foreach ($unsafe_exts as $unsafe_extension) {
          $fields[$entity->id()][] = $unsafe_extension;
        }
      }
    }

    if (!empty($fields)) {
      $items = [];
      foreach ($fields as $entity_id => $unsafe_extensions) {
        $entity = FieldConfig::load($entity_id);
        foreach ($unsafe_extensions as $extension) {
          $items[] = $this->t(
            'Review @type in <em>@field</em> field on @bundle',
            [
              '@type' => $extension,
              '@field' => $entity->label(),
              '@bundle' => $entity->getTargetBundle(),
            ]
          )->render();
        }
      }

      $params = ['fields' => $items];
      return $this->fail('Unsafe file extensions are allowed in uploads.', $params);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      $arguments = $reason->getArguments();
      if (!empty($arguments['fields'])) {
        $build['unsafe_extensions'] = [
          '#theme' => 'item_list',
          '#title' => $this->t('Fields with unsafe extensions:'),
          '#list_type' => 'ul',
          '#items' => $arguments['fields'],
        ];
        return $build;
      }
    }

    return [];
  }

}
