<?php

namespace Drupal\adv_audit\Entity;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponse;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the Audit Result entity entity.
 *
 * @ingroup adv_audit
 *
 * @ContentEntityType(
 *   id = "adv_audit",
 *   label = @Translation("Audit Result"),
 *   handlers = {
 *     "storage" = "Drupal\adv_audit\Entity\AuditEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\adv_audit\Entity\AuditEntityListBuilder",
 *     "views_data" = "Drupal\adv_audit\Entity\AuditEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\adv_audit\Form\AuditEntityForm",
 *       "add" = "Drupal\adv_audit\Form\AuditEntityForm",
 *       "edit" = "Drupal\adv_audit\Form\AuditEntityForm",
 *       "delete" = "Drupal\adv_audit\Form\AuditEntityDeleteForm",
 *     },
 *     "access" = "Drupal\adv_audit\Access\AuditEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\adv_audit\Entity\AuditEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "adv_audit",
 *   data_table = "adv_audit_field",
 *   revision_table = "adv_audit_revision",
 *   revision_data_table = "adv_audit_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer audit result entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/reports/adv-audit/{adv_audit}",
 *     "add-form" = "/admin/reports/adv-audit/add",
 *     "edit-form" = "/admin/reports/adv-audit/{adv_audit}/edit",
 *     "delete-form" = "/admin/reports/adv-audit/{adv_audit}/delete",
 *     "version-history" = "/admin/reports/adv-audit/{adv_audit}/revisions",
 *     "revision" = "/admin/reports/adv-audit/{adv_audit}/revisions/{adv_audit_revision}/view",
 *     "revision_revert" = "/admin/reports/adv-audit/{adv_audit}/revisions/{adv_audit_revision}/revert",
 *     "revision_delete" = "/admin/reports/adv-audit/{adv_audit}/revisions/{adv_audit_revision}/delete",
 *     "collection" = "/admin/reports/adv-audit",
 *   },
 *   field_ui_base_route = "adv_audit.settings"
 * )
 */
class AuditEntity extends RevisionableContentEntityBase implements AuditEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($this instanceof RevisionableInterface && in_array($rel, ['revision_revert', 'revision_delete'])) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the adv_audit owner
    // the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return User::load(1);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    return $this;
  }

  /**
   * Set Issues if any.
   *
   * @param \Drupal\adv_audit\AuditResultResponse $result
   *
   * @return $this
   */
  public function setIssues(AuditResultResponse $result) {
    $audit_results = $result->getAuditResults();
    $audit_issues = [];

    foreach ($audit_results as $audit_result) {
      $plugin_id = $audit_result['testId'];
      $status = $audit_result['status'];
      $reason = $audit_result['reason'];
      $arguments = $audit_result['arguments'];
      $issues = $audit_result['issues'];
      $audit_reason = new AuditReason($plugin_id, $status, $reason, $arguments);
      $audit_reason->setIssues($issues);
      $audit_issue = $audit_reason->reportIssues();
      $audit_issues += $audit_issue;
    }

    if (!empty($audit_issues)) {
      $this->set('issues', $audit_issues);
    }

    return $this;
  }

  /**
   * Set audit results.
   *
   * @param \Drupal\adv_audit\AuditResultResponse $result
   *
   * @return $this
   */
  public function setAuditResults(AuditResultResponse $result) {
    $this->set('audit_results', $result->serialize());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateEntityName() {
    return 'Audit Report from ' . date(DATE_RFC3339, time()) . ' by ' . \Drupal::currentUser()->getAccountName();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Audit Result entity entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 100,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // Issues.
    $fields['issues'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Issues'))
      ->setDescription(new TranslatableMarkup('The issues that were found in the audit.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'adv_audit_issue')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);

    // Field for storing of Audit Results.
    $fields['audit_results'] = BaseFieldDefinition::create('audit_result')
      ->setLabel(t('Audit Results'))
      ->setDescription(t('Output results of audit.'))
      ->setDefaultValue(NULL)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'audit_report_formatter',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'audit_report_widget',
        'settings' => [
          'rows' => 10,
        ],
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
