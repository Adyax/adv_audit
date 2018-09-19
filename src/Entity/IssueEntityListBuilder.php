<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Audit Issue entities.
 *
 * @ingroup adv_audit
 */
class IssueEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Audit Issue ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\adv_audit\Entity\IssueEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.adv_audit_issue.edit_form',
      ['adv_audit_issue' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
