<?php

namespace Drupal\adv_audit\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Doctrine\Common\Annotations\AnnotationReader;
use Drupal\adv_audit\Annotation\AdvAuditCheckpointAnnotation;

/**
 * Base class for Example plugin plugins.
 */
abstract class AdvAuditCheckpointBase extends PluginBase implements AdvAuditCheckpointInterface {

  /**
   * Return string with check status.
   *
   * @return string
   *   possible values: 'success', 'fail', 'process'.
   */
  public function getProcessStatus() {
    return 'test';
  }

  /**
   * Set check status.
   *
   * @param string $status
   *   Possible values: 'success', 'fail', 'process'.
   */
  public function setProcessStatus($status) {

  }

  /**
   * Return stored from last checking data.
   *
   * @return mixed
   *   Array results where every item is associated array with keys.
   */
  public function getRecentReport() {
    return [];
  }

  /**
   * Process checkpoint review.
   */
  abstract public function process();

//  /**
//   * {@inheritdoc}
//   */
//  public static function getInformation() {
//    $annotationReader = new AnnotationReader();
//    $annotationObject = new AdvAuditCheckpointAnnotation();
//    $reflectionObject = new \ReflectionObject($annotationObject);
//    return $annotationReader->getClassAnnotations($reflectionObject);
//  }

}
