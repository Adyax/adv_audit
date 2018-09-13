<?php

namespace Drupal\adv_audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class of the Help pages' controller.
 */
class AuditHelpController extends ControllerBase {

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * Constructs a HelpController.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date.formatter service.
   */
  public function __construct(DateFormatterInterface $dateFormatter) {
    // Store the dependencies.
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * Serves as an entry point for the help pages.
   *
   * @param string|null $namespace
   *   The namespace of the check (null if general page).
   * @param string $title
   *   The name of the check.
   *
   * @return array
   *   The requested help page.
   */
  public function index($namespace, $title) {
    // If no namespace is set, print the general help page.
    if ($namespace === NULL) {
      return $this->generalHelp();
    }

    // Print check-specific help.
    return $this->checkHelp($namespace, $title);
  }

  /**
   * Returns the general help page.
   *
   * @return array
   *   The general help page.
   */
  private function generalHelp() {
    $paragraphs = [];
    $checks = [];
    // Print the general help.
    $paragraphs[] = $this->t('You should take the security of your site very seriously. Fortunately, Drupal is fairly secure by default. The Security Review module automates many of the easy-to-make mistakes that render your site insecure, however it does not automatically make your site impenetrable. You should give care to what modules you install and how you configure your site and server. Be mindful of who visits your site and what features you expose for their use.');
    $paragraphs[] = $this->t('You can read more about securing your site in the <a href="http://drupal.org/security/secure-configuration">drupal.org handbooks</a> and on <a href="http://crackingdrupal.com">CrackingDrupal.com</a>. There are also additional modules you can install to secure or protect your site. Be aware though that the more modules you have running on your site the greater (usually) attack area you expose.');
    $paragraphs[] = $this->t('<a href="http://drupal.org/node/382752">Drupal.org Handbook: Introduction to security-related contrib modules</a>');

    return [
      '#theme' => 'general_help',
      '#paragraphs' => $paragraphs,
      '#checks' => $checks,
    ];
  }

}
