<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the audit preset entity.
 *
 * @ConfigEntityType(
 *   id = "adv_audit_preset_entity",
 *   label = @Translation("Audit preset"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\adv_audit\Entity\AuditPresetEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\adv_audit\Form\AuditPresetEntityForm",
 *       "edit" = "Drupal\adv_audit\Form\AuditPresetEntityForm",
 *       "delete" = "Drupal\adv_audit\Form\AuditPresetEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\adv_audit\Controller\AuditPresetEntityHtmlRouteProvider",
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
 *     "canonical" = "/admin/reports/adv-audit/preset/{adv_audit_preset_entity}",
 *     "add-form" = "/admin/reports/adv-audit/preset/add",
 *     "edit-form" = "/admin/reports/adv-audit/preset/{adv_audit_preset_entity}/edit",
 *     "delete-form" = "/admin/reports/adv-audit/preset/{adv_audit_preset_entity}/delete",
 *     "collection" = "/admin/reports/adv-audit/preset"
 *   }
 * )
 */
class AuditPresetEntity extends ConfigEntityBase implements AuditPresetEntityInterface {

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
