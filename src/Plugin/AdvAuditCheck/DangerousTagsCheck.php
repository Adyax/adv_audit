<?php

namespace Drupal\adv_audit\Plugin\AdvAuditCheck;

use Drupal\adv_audit\Plugin\AdvAuditCheckBase;
use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\Renderer\AdvAuditReasonRenderableInterface;
use Drupal\adv_audit\Message\AuditMessagesStorageInterface;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Trusted Host Check plugin class.
 *
 * @AdvAuditCheck(
 *   id = "dangerous_tags_check",
 *   label = @Translation("Dangerous Tags"),
 *   category = "security",
 *   requirements = {},
 *   enabled = true,
 *   severity = "high"
 * )
 */
class DangerousTagsCheck extends AdvAuditCheckBase implements AdvAuditReasonRenderableInterface, ContainerFactoryPluginInterface {
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
  public function configForm() {
    $settings = $this->getPerformSettings();

    $form['formats'] = [
      '#type' => 'checkboxes',
      '#options' => ['text_with_summary', 'text_long'],
      '#default_value' => $settings['formats'],
    ];
    $form['tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Dangerous tags'),
      '#default_value' => $settings['tags'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    $value = $form_state->getValue('additional-settings');
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
      'formats' => [
        'text_with_summary',
        'text_long',
      ],
      'tags' => 'script,?php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function perform() {
    $params = [];
    $results = [];

    $field_types = [
      'text_with_summary',
      'text_long',
    ];
    $tags = [
      'Javascript' => 'script',
      'PHP' => '?php',
    ];

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $field_manager = \Drupal::service('entity_field.manager');
    foreach ($field_manager->getFieldMap() as $entity_type_id => $fields) {
      $field_storage_definitions = $field_manager->getFieldStorageDefinitions($entity_type_id);
      foreach ($fields as $field_name => $field) {
        if (!isset($field_storage_definitions[$field_name])) {
          continue;
        }
        $field_storage_definition = $field_storage_definitions[$field_name];
        if (in_array($field_storage_definition->getType(), $field_types)) {
          if ($field_storage_definition instanceof FieldStorageConfig) {
            $table = $entity_type_id . '__' . $field_name;
            $separator = '_';
            $id = 'entity_id';
          }
          else {
            $table = $entity_type_id . '_field_data';
            $separator = '__';
            $id = $entity_type_manager->getDefinition($entity_type_id)->getKey('id');
          }
          $rows = \Drupal::database()->select($table, 't')
            ->fields('t')
            ->execute()
            ->fetchAll();
          foreach ($rows as $row) {
            foreach (array_keys($field_storage_definition->getSchema()['columns']) as $column) {
              $column_name = $field_name . $separator . $column;
              foreach ($tags as $vulnerability => $tag) {
                if (strpos($row->{$column_name}, '<' . $tag) !== FALSE) {
                  // Vulnerability found.
                  $results[$entity_type_id][$row->{$id}][$field_name][] = $vulnerability;
                }
              }
            }
          }
        }
      }
    }

    if (!empty($results)) {
      $params = ['fields' => $results];
      return $this->fail('Dangerous tags were found in submitted content (fields).', $params);
    }

    return $this->success();
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
      foreach ($arguments['fields'] as $entity_type_id => $entities) {
        foreach ($entities as $entity_id => $fields) {
          $entity = $this->entityManager()
            ->getStorage($entity_type_id)
            ->load($entity_id);

          foreach ($fields as $field => $finding) {
            $items[] = $this->t(
              '@vulnerabilities found in <em>@field</em> field of <a href=":url">@label</a>',
              [
                '@vulnerabilities' => implode(' and ', $finding),
                '@field' => $field,
                '@label' => $entity->label(),
                ':url' => $this->getEntityLink($entity),
              ]
            );
          }
        }
      }

      return [
        '#theme' => 'item_list',
        '#title' => $this->t('The following items potentially have dangerous tags:'),
        '#list_type' => 'ol',
        '#items' => $items,
      ];
    }

    return [];
  }

}
