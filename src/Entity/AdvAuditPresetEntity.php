<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Adv audit preset entity entity.
 *
 * @ConfigEntityType(
 *   id = "adv_audit_preset_entity",
 *   label = @Translation("Adv audit preset entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\adv_audit\AdvAuditPresetEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\adv_audit\Form\AdvAuditPresetEntityForm",
 *       "edit" = "Drupal\adv_audit\Form\AdvAuditPresetEntityForm",
 *       "delete" = "Drupal\adv_audit\Form\AdvAuditPresetEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\adv_audit\AdvAuditPresetEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "preset",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/adv-audit/preset/{adv_audit_preset_entity}",
 *     "add-form" = "/admin/config/adv-audit/preset/add",
 *     "edit-form" = "/admin/config/adv-audit/preset/{adv_audit_preset_entity}/edit",
 *     "delete-form" = "/admin/config/adv-audit/preset/{adv_audit_preset_entity}/delete",
 *     "collection" = "/admin/config/adv-audit/preset"
 *   }
 * )
 */
class AdvAuditPresetEntity extends ConfigEntityBase implements AdvAuditPresetEntityInterface {

  /**
   * The Adv audit preset entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Adv audit preset entity label.
   *
   * @var string
   */
  protected $label;

}
