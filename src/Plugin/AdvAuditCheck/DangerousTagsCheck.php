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
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\Entity;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Dangerous Tags Check plugin class.
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
   * Entity Type Manager container.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The state service object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, EntityTypeManager $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
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

    $form['field_types'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'text_with_summary' => 'text_with_summary',
        'text_long' => 'text_long',
      ],
      '#default_value' => $settings['field_types'],
    ];
    $form['tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Dangerous tags'),
      '#default_value' => $settings['tags'],
      '#description' => $this->t('List of dangerous tags, separated with coma without spaces.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormSubmit($form, FormStateInterface $form_state) {
    $value = $form_state->getValue('additional_settings');
    foreach ($value['plugin_config']['field_types'] as $key => $format) {
      if (!$format) {
        unset($value['plugin_config']['field_types'][$key]);
      }
    }
    $this->state->set($this->buildStateConfigKey(), $value['plugin_config']);
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
      'field_types' => [
        'text_with_summary', 'text_long',
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
    $settings = $this->getPerformSettings();
    $field_types = $settings['field_types'];
    $tags = explode(',', $settings['tags']);

    foreach ($this->entityFieldManager->getFieldMap() as $entity_type_id => $fields) {
      $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      foreach ($fields as $field_name => $field) {
        if (!isset($field_storage_definitions[$field_name])) {
          continue;
        }
        $field_storage_definition = $field_storage_definitions[$field_name];
        if (!in_array($field_storage_definition->getType(), $field_types)) {
          continue;
        }

        $table = $entity_type_id . '_field_data';
        $separator = '__';
        $id = $this->entityTypeManager->getDefinition($entity_type_id)->getKey('id');
        if ($field_storage_definition instanceof FieldStorageConfig) {
          $table = $entity_type_id . '__' . $field_name;
          $separator = '_';
          $id = 'entity_id';
        }

        $rows = \Drupal::database()->select($table, 't')
          ->fields('t')
          ->execute()
          ->fetchAll();
        $columns = array_keys($field_storage_definition->getSchema()['columns']);
        $results += $this->getVulnerabilities($rows, $columns, $field_name, $separator, $tags, $entity_type_id, $id);
      }
    }

    if (!empty($results)) {
      $params = ['fields' => $results];
      return $this->fail($this->t('Dangerous tags were found in submitted content (fields).'), $params);
    }

    return $this->success();
  }

  /**
   * Get Vulnerabilities in content.
   *
   * Falls back on a string with entity type id and id if no good link can
   * be found.
   *
   * @param array $rows
   *   The rows.
   * @param array $columns
   *   The columns.
   * @param string $field_name
   *   The field name.
   * @param string $separator
   *   The separator.
   * @param array $tags
   *   The tags array.
   * @param string $entity_type_id
   *   The entity type id.
   * @param int $id
   *   The entity id.
   *
   * @return array
   *   The Vulnerabilities array.
   */
  protected function getVulnerabilities(array $rows, array $columns, $field_name, $separator, array $tags, $entity_type_id, $id) {
    $content = [];
    foreach ($rows as $row) {
      foreach ($columns as $column) {
        $column_name = $field_name . $separator . $column;
        foreach ($tags as $tag) {
          if (strpos($row->{$column_name}, '<' . $tag) !== FALSE) {
            // Vulnerability found.
            $content[$entity_type_id][$row->{$id}][$field_name][] = $tag;
          }
        }
      }
    }

    return $content;
  }

  /**
   * Get link for the entity.
   *
   * Falls back on a string with entity type id and id if the link can
   * be found.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   The entity.
   *
   * @return string
   *   The Entity URL.
   */
  protected function getEntityLink(Entity $entity) {
    try {
      $url = $entity->toUrl('edit-form');
    }
    catch (UndefinedLinkTemplateException $e) {
      $url = NULL;
    }
    if ($url === NULL) {
      try {
        $url = $entity->toUrl();
      }
      catch (UndefinedLinkTemplateException $e) {
        $url = NULL;
      }
    }

    return $url !== NULL ? $url->toString() : ($entity->getEntityTypeId() . ':' . $entity->id());
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
          $entity = \Drupal::entityTypeManager()
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
