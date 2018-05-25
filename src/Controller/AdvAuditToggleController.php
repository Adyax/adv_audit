<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Responsible for handling the toggle links on the Run & Review page.
 */
class AdvAuditToggleController extends ControllerBase {

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The request stack.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a ToggleController.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF Token generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   */
  public function __construct(CsrfTokenGenerator $csrf_token_generator, RequestStack $request) {
    $this->csrfToken = $csrf_token_generator;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token'),
      $container->get('request_stack')
    );
  }

  /**
   * Handles check toggling.
   *
   * @param string $check_id
   *   The ID of the check.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function index($check_id) {
    // Go back to Run & Review if the access was wrong.
    return $this->redirect('adv_audit');
  }

}
