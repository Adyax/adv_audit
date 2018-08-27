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
 * Defines the Audit Issue entity.
 *
 * @ingroup adv_audit
 *
 * @ContentEntityType(
 *   id = "adv_audit_issue",
 *   label = @Translation("Audit Issue"),
 *   handlers = {
 *     "storage" = "Drupal\adv_audit\IssueEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\adv_audit\IssueEntityListBuilder",
 *     "views_data" = "Drupal\adv_audit\Entity\IssueEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\adv_audit\Form\IssueEntityForm",
 *       "add" = "Drupal\adv_audit\Form\IssueEntityForm",
 *       "edit" = "Drupal\adv_audit\Form\IssueEntityForm",
 *       "delete" = "Drupal\adv_audit\Form\IssueEntityDeleteForm",
 *     },
 *     "access" = "Drupal\adv_audit\IssueEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\adv_audit\IssueEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "adv_audit_issue",
 *   revision_table = "adv_audit_issue_revision",
 *   revision_data_table = "adv_audit_issue_field_revision",
 *   admin_permission = "administer audit issue entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "name" = "name",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/reports/adv-audit/issues/adv_audit_issue/{adv_audit_issue}",
 *     "add-form" = "/admin/reports/adv-audit/issues/adv_audit_issue/add",
 *     "edit-form" = "/admin/reports/adv-audit/issues/adv_audit_issue/{adv_audit_issue}/edit",
 *     "delete-form" = "/admin/reports/adv-audit/issues/adv_audit_issue/{adv_audit_issue}/delete",
 *     "version-history" = "/admin/reports/adv-audit/issues/adv_audit_issue/{adv_audit_issue}/revisions",
 *     "revision" = "/admin/reports/adv-audit/issues/adv_audit_issue/{adv_audit_issue}/revisions/{adv_audit_issue_revision}/view",
 *     "revision_revert" = "/admin/reports/adv-audit/issues/adv_audit_issue/{adv_audit_issue}/revisions/{adv_audit_issue_revision}/revert",
 *     "revision_delete" = "/admin/reports/adv-audit/issues/adv_audit_issue/{adv_audit_issue}/revisions/{adv_audit_issue_revision}/delete",
 *     "collection" = "/admin/reports/adv-audit/issues/adv_audit_issue",
 *   },
 *   field_ui_base_route = "adv_audit_issue.settings"
 * )
 */
class IssueEntity extends RevisionableContentEntityBase implements IssueEntityInterface {

  use EntityChangedTrait;

  const STATUS_OPEN = 'open';
  const STATUS_FIXED = 'fixed';
  const STATUS_REJECTED = 'rejected';

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

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
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

    // If no revision author has been set explicitly,
    // make the adv_audit_issue owner the revision author.
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
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($name) {
    $this->set('Title', $name);
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
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    // Check if a valid status is given.
    if (array_key_exists($status, static::getStatuses())) {
      $this->status = $status;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isStatus($state) {
    return $this->getStatus() == $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStatuses() {
    return [static::STATUS_OPEN, static::STATUS_FIXED, static::STATUS_REJECTED];
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Field for unique Audit Issue identifier.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Audit Issue entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
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
      ->setRequired(TRUE)
      ->addConstraint('UniqueField');

    // Field for Audit Issue title.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the issue.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ]);

    // Field for Audit Issue status.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Issue status'))
      ->setSetting('allowed_values', static::getStatuses())
      ->setDefaultValue(static::STATUS_OPEN)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    if (!empty($values['name'])) {
      // Do not create new issue if the same exists.
      $issue = static::loadByName($values['name']);
      if (!empty($issue)) {
        return $issue;
      }
    }

    return parent::create($values);
  }

  /**
   * Load Issue by it's unique name.
   *
   * @param string $name
   *   Unique issue name.
   */
  public static function loadByName($name) {
    $entities = \Drupal::entityTypeManager()->getStorage('adv_audit_issue')->loadByProperties(['name' => $name]);
    return empty($entities) ? NULL : reset($entities);
  }

}
