<?php

namespace Drupal\adv_audit\Plugin\AuditPlugins;

use Drupal\adv_audit\Traits\AuditPluginSubform;
use Drupal\adv_audit\Plugin\AuditBasePlugin;
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
 * )
 */
class SecurityUnsafeExtensionsPlugin extends AuditBasePlugin implements PluginFormInterface {

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
      $issues = [];
      foreach ($fields as $field => $exts) {
        $entity = FieldConfig::load($field);
        foreach ($exts as $ext) {
          $issues[] = [
            '@issue_title' => 'Unsafe file extension "@ext" is allowed in field @field on @bundle.',
            '@ext' => $ext,
            '@field' => $entity->label(),
            '@bundle' => $entity->getTargetBundle(),
          ];
        }
      }
      return $this->fail(NULL, ['issues' => $issues]);
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

}
