<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
 *     "storage" = "Drupal\adv_audit\Entity\IssueEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\adv_audit\Entity\IssueEntityListBuilder",
 *     "views_data" = "Drupal\adv_audit\Entity\IssueEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\adv_audit\Form\IssueEntityForm",
 *       "add" = "Drupal\adv_audit\Form\IssueEntityForm",
 *       "edit" = "Drupal\adv_audit\Form\IssueEntityForm",
 *       "delete" = "Drupal\adv_audit\Form\IssueEntityDeleteForm",
 *     },
 *     "access" = "Drupal\adv_audit\Entity\IssueEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\adv_audit\Entity\IssueEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "adv_audit_issue",
 *   data_table = "adv_audit_issue_field_data",
 *   revision_table = "adv_audit_issue_revision",
 *   revision_data_table = "adv_audit_issue_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer audit issue entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "plugin" = "plugin",
 *     "name" = "name",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "details" = "details",
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
  const STATUS_IGNORED = 'ignored';

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

    if (!($this instanceof RevisionableInterface)) {
      return $uri_route_parameters;
    }

    if ($rel === 'revision_revert' || $rel === 'revision_delete') {
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
    $this->set('title', $name);
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
  public function isOpen() {
    return $this->getStatus() == static::STATUS_OPEN;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStatuses() {
    return [
      static::STATUS_OPEN => static::STATUS_OPEN,
      static::STATUS_IGNORED => static::STATUS_IGNORED,
    ];
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
    // Do nothing.
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
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    return $this->get('details')->getValue()[0];
  }

  /**
   * {@inheritdoc}
   */
  public function setDetails($details) {
    return $this->details = $details;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Field for unique Audit Issue identifier.
    $fields['plugin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin'))
      ->setDescription(t('The plugin raising the Audit Issue'))
      ->setRevisionable(FALSE)
      ->setSettings([
        'max_length' => 128,
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

    // Field for unique Audit Issue identifier.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Audit Issue entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 256,
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
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', static::getStatuses())
      ->setDefaultValue(static::STATUS_OPEN)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ]);

    // Field for storing of Audit Results.
    $fields['details'] = BaseFieldDefinition::create('audit_result')
      ->setLabel(t('Issue Details'))
      ->setDescription(t('Audit issue details.'))
      ->setDefaultValue(NULL)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
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

    if (empty($values['title'])) {
      $values['title'] = $values['name'];
    }

    if (empty($values['status'])) {
      $values['status'] = static::STATUS_OPEN;
    }

    return parent::create($values);
  }

  /**
   * Load Issue by it's unique name.
   *
   * @param string $name
   *   The unique name of Audit Issue.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object, or NULL if there is no entity with the given name.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function loadByName($name) {
    $entities = \Drupal::entityTypeManager()->getStorage('adv_audit_issue')->loadByProperties(['name' => $name]);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * Get printable Audit Issue for report.
   */
  public function __toString() {
    return (string) $this->getMarkup();
  }

  /**
   * Get printable Audit Issue for report as FormattableMarkup.
   */
  public function getMarkup() {
    return new FormattableMarkup($this->getTitle(), $this->getDetails());
  }

}
