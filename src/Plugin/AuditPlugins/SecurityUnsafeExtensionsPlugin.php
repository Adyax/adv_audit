<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Form\FormStateInterface;

/**
 * Unsafe extensions Check plugin class.
 *
 * @AuditPlugin(
 *   id = "unsafe_extensions_check",
 *   label = @Translation("Unsafe extensions"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class SecurityUnsafeExtensionsPlugin extends AuditBasePlugin implements AdvAuditReasonRenderableInterface, PluginFormInterface {

  use AuditPluginSubform;
  /**
   * {@inheritdoc}
   */
  public function perform() {
    $fields = [];
    $settings = $this->getSettings();
    $unsafe_ext = explode(',', $settings['unsafe_extensions']);
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
      $params = ['fields' => $fields];
      return $this->fail('Unsafe file extensions are allowed in uploads.', $params);
    }

    return $this->success();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form['unsafe_extensions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Unsafe file extensions'),
      '#default_value' => $settings['unsafe_extensions'],
      '#description' => $this->t('List of unsafe file extensions, separated with coma without spaces.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      $arguments = $reason->getArguments();
      if (empty($arguments['fields'])) {
        return [];
      }

      $items = [];
      foreach ($arguments['fields'] as $entity_id => $unsafe_extensions) {
        $entity = FieldConfig::load($entity_id);
        foreach ($unsafe_extensions as $extension) {
          $items[] = $this->t(
            'Review @type in "@field" field on @bundle',
            [
              '@type' => $extension,
              '@field' => $entity->label(),
              '@bundle' => $entity->getTargetBundle(),
            ]
          );
        }
      }

      return [
        '#theme' => 'item_list',
        '#title' => $this->t('Fields with unsafe extensions:'),
        '#list_type' => 'ol',
        '#items' => $items,
      ];
    }

    return [];
  }

}
