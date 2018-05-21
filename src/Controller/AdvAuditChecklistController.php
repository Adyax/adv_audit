<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class of the 'Run & Review' page's controller.
 */
class DrupalAuditorChecklistController extends ControllerBase {

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   */
  protected $csrfToken;

  /**
   * DrupalAuditorChecklistController constructor.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF Token generator.
   */
  public function __construct(CsrfTokenGenerator $csrf_token_generator) {
    $this->csrfToken = $csrf_token_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token')
    );
  }

  /**
   * Creates the Run & Review page.
   *
   * @return array
   *   The 'Run & Review' page's render array.
   */
  public function index() {
    $run_form = [];

    return 'Dummy page';
  }

  /**
   * Creates the results' table.
   *
   * @return array
   *   The render array for the result table.
   */
  public function results() {

//    return [
//      '#theme' => 'run_and_review',
//      '#date' => $this->drupalAuditor->getLastRun(),
//      '#checks' => $checks,
//      '#attached' => [
//        'library' => ['adv_audit/run_and_review'],
//      ],
//    ];
    return 'Dammy results';
  }

}
