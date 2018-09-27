<?php

namespace Drupal\adv_audit\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * The class of the Pdf generation' controller.
 */
class AuditHtmlReportController {

  /**
   * Public function view.
   */
  public function getReportHtml($adv_audit) {

    $entity_type = 'adv_audit';
    $view_mode = 'html';
    $entity_report = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->load($adv_audit->id->value);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $build = $view_builder->view($entity_report, $view_mode);
    $renderer = render($build);

    $stylesheet = file_get_contents(DRUPAL_ROOT . '/' . drupal_get_path('module', 'adv_audit') . '/css/view_html_results.css');

    $renderer = '<style media="all">' . $stylesheet . '</style>' . $renderer;

    $renderer = preg_replace('/\\n/', '', $renderer);

    $headers = [
      'Content-Type: text/html',
      'charset=utf-8'
    ];

    return new Response($renderer, 200, $headers);
  }

}
