<?php

namespace Drupal\adv_audit\Entity;

use Drupal\adv_audit\AuditReason;
use Drupal\adv_audit\AuditResultResponse;
use Drupal\adv_audit\AuditResultResponseInterface;
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
    $audit_results = $entity->get('audit_results')->first()->getValue();

    /** @var AuditResultResponse $audit_result */
    $audit_result = new AuditResultResponse();
    if (!empty($audit_results['results'])) {
      foreach ($audit_results['results'] as $result) {
        $plugin_id = $result['testId'];
        $status = $result['status'];
        $reason = $result['reason'];
        $arguments = $result['arguments'];
        $issues = $result['issues'];
        $reason = new AuditReason($plugin_id, $status, $reason, $arguments);
        $reason->setIssues($issues);
        $audit_result->addReason($reason, false);
      }
    }
    if (!empty($audit_results['overviewInfo'])) {
      $audit_result->setOverviewInfo($audit_results['overviewInfo']);
    }
    if ($audit_result instanceof AuditResultResponseInterface) {
      $row['score'] = $audit_result->calculateScore();
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'), 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
