<?php

namespace Drupal\adv_audit\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Audit Issue entities.
 */
class IssueEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
