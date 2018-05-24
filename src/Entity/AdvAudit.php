<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines the Audit Result entity.
 *
 * @ingroup adv_audit
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "adv_audit",
 *   label = @Translation("Audit Result entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\adv_audit\Entity\Controller\AdvAuditListBuilder",
 *     "form" = {
 *       "add" = "Drupal\adv_audit\Form\AdvAuditForm",
 *       "edit" = "Drupal\adv_audit\Form\AdvAuditForm",
 *       "delete" = "Drupal\adv_audit\Form\AdvAuditDeleteForm",
 *     },
 *     "access" = "Drupal\adv_audit\AdvAuditAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "adv_audit",
 *   admin_permission = "administer adv_audit entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/adv_audit_results/{adv_audit}",
 *     "edit-form" = "/adv_audit_results/{adv_audit}/edit",
 *     "delete-form" = "/adv_audit_results/{adv_audit}/delete",
 *     "collection" = "/adv_audit_results/list"
 *   },
 *   field_ui_base_route = "adv_audit.settings",
 * )
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.content_entity_example_contact.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The Contact class defines methods and fields for the contact entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see ContactInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 *
 * The class also uses the EntityChangedTrait trait which allows it to record
 * timestamps of save operations.
 */
class AdvAudit extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
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
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Load the site name out of configuration.
    $site_name = \Drupal::config('system.site')->get('name');
    $site_name = $site_name ? $site_name : 'Drupal';
    $dt = new DrupalDateTime();

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Audit Result entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Audit Result entity.'))
      ->setReadOnly(TRUE);

    // Title of entity.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The name of the Elis family entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue($site_name . ' - ' . $dt->format('Y-m-d H:i:s'))
      ->setTranslatable(TRUE)
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
      ->setDisplayConfigurable('view', TRUE);

    // Field for storing of Audit Results.
    $fields['audit_results'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Audit Results'))
      ->setDescription(t('Ready HTML of audit results.'))
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'settings' => [
          'rows' => 10,
        ],
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Audit in pdf format.
    $fields['pdf'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Audit in pdf format'))
      ->setSettings([
        'file_extensions' => 'pdf',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'file',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'file',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Created value.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    // Changed value.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
