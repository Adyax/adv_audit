<?php

namespace Drupal\adv_audit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Audit Result entity entities.
 *
 * @ingroup adv_audit
 */
class AdvAuditEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Audit Result entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\adv_audit\Entity\AdvAuditEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.adv_audit.edit_form',
      ['adv_audit' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
