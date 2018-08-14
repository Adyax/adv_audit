<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the Audit Result entity entity.
 *
 * @ingroup adv_audit
 *
 * @ContentEntityType(
 *   id = "adv_audit",
 *   label = @Translation("Audit Result entity"),
 *   handlers = {
 *     "storage" = "Drupal\adv_audit\AdvAuditEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\adv_audit\AdvAuditEntityListBuilder",
 *     "views_data" = "Drupal\adv_audit\Entity\AdvAuditEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\adv_audit\Form\AdvAuditEntityForm",
 *       "add" = "Drupal\adv_audit\Form\AdvAuditEntityForm",
 *       "edit" = "Drupal\adv_audit\Form\AdvAuditEntityForm",
 *       "delete" = "Drupal\adv_audit\Form\AdvAuditEntityDeleteForm",
 *     },
 *     "access" = "Drupal\adv_audit\AdvAuditEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\adv_audit\AdvAuditEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "adv_audit",
 *   revision_table = "adv_audit_revision",
 *   revision_data_table = "adv_audit_field_revision",
 *   admin_permission = "administer audit result entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/adv_audit_results/adv_audit/{adv_audit}",
 *     "add-form" = "/adv_audit_results/adv_audit/add",
 *     "edit-form" = "/adv_audit_results/adv_audit/{adv_audit}/edit",
 *     "delete-form" = "/adv_audit_results/adv_audit/{adv_audit}/delete",
 *     "version-history" = "/adv_audit_results/adv_audit/{adv_audit}/revisions",
 *     "revision" = "/adv_audit_results/adv_audit/{adv_audit}/revisions/{adv_audit_revision}/view",
 *     "revision_revert" = "/adv_audit_results/adv_audit/{adv_audit}/revisions/{adv_audit_revision}/revert",
 *     "revision_delete" = "/adv_audit_results/adv_audit/{adv_audit}/revisions/{adv_audit_revision}/delete",
 *     "collection" = "/adv_audit_results/adv_audit",
 *   },
 *   field_ui_base_route = "adv_audit.settings"
 * )
 */
class AdvAuditEntity extends RevisionableContentEntityBase implements AdvAuditEntityInterface {

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
    if ($this instanceof RevisionableInterface) {
      if ($rel === 'revision_revert' || $rel === 'revision_revert') {
        $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
      }
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
