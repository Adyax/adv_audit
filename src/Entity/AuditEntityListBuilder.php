<?php

namespace Drupal\adv_audit\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Audit Result entity entities.
 *
 * @ingroup adv_audit
 */
class AuditEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Report ID');
    $header['name'] = $this->t('Name');
    $header['score'] = $this->t('Score point');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\adv_audit\Entity\AuditEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.adv_audit.canonical',
      ['adv_audit' => $entity->id()]
    );
    $row['score'] = 0;
    $result = $entity->get('audit_results')->first()->getValue();
    if ($result instanceof AuditResultResponseInterface) {
      $row['score'] = $result->calculateScore();
    }
    return $row + parent::buildRow($entity);
  }

}
