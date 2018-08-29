<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

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
class UnsafeExtensionsCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, ContainerFactoryPluginInterface {
  /**
   * The state service object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * Build key string for access to stored value from config.
   *
   * @return string
   *   The generated key.
   */
  protected function buildStateConfigKey() {
    return 'adv_audit.plugin.' . $this->id() . '.additional-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $fields = [];
    $settings = $this->getPerformSettings();
    $unsafe_ext = explode(' ', $settings['unsafe_extensions']);
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
  public function configForm() {
    $settings = $this->getPerformSettings();
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
  public function configFormSubmit($form, FormStateInterface $form_state) {
    $value = $form_state->getValue('unsafe_extensions');
    $this->state->set($this->buildStateConfigKey(), $value);
  }

  /**
   * Get settings for perform task.
   */
  protected function getPerformSettings() {
    $settings = $this->state->get($this->buildStateConfigKey());
    return !is_null($settings) ? $settings : $this->getDefaultPerformSettings();
  }

  /**
   * Get default settings.
   */
  protected function getDefaultPerformSettings() {
    return [
      'unsafe_extensions' => 'swf,exe,html,htm,php,phtml,py,js,vb,vbe,vbs',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function auditReportRender(AuditReason $reason, $type) {
    if ($type == AuditMessagesStorageInterface::MSG_TYPE_FAIL) {
      $arguments = $reason->getArguments();
      if (!empty($arguments['fields'])) {
        $items = [];
        $fields = $arguments['fields'];
        if (!empty($fields)) {
          foreach ($fields as $entity_id => $unsafe_extensions) {
            $entity = FieldConfig::load($entity_id);
            foreach ($unsafe_extensions as $extension) {
              $items[] = $this->t(
                'Review @type in "@field" field on @bundle',
                [
                  '@type' => $extension,
                  '@field' => $entity->label(),
                  '@bundle' => $entity->getTargetBundle(),
                ]
              )->render();
            }
          }
        }

        return [
          '#theme' => 'item_list',
          '#title' => $this->t('Fields with unsafe extensions:'),
          '#list_type' => 'ul',
          '#items' => $items,
        ];
      }
    }

    return [];
  }

}
