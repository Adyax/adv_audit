<?php

namespace Drupal\adv_audit\Tests;

use Drupal\adv_audit\Checklist;
use Drupal\simpletest\WebTestBase;

/**
 * Contains tests related to the DrupalAuditor class.
 *
 * @group adv_audit
 */
class ChecklistWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['adv_audit'];

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The security checks defined by Security Review.
   *
   * @var \Drupal\adv_audit\Check[]
   */
  protected $checks;

  /**
   * The adv_audit.checklist service.
   *
   * @var \Drupal\adv_audit\Checklist
   */
  protected $checklist;

  /**
   * Sets up the testing environment.
   */
  protected function setUp() {
    parent::setUp();

    $this->checklist = \Drupal::getContainer()
      ->get('adv_audit.checklist');

    // Login.
    $this->user = $this->drupalCreateUser(
      [
        'run audit',
        'access auditor list',
        'access administration pages',
        'administer site configuration',
      ]
    );
    $this->drupalLogin($this->user);

    // Populate $checks.

    // Clear cache.
    Checklist::clearCache();
  }

  /**
   * Tests a full checklist run.
   *
   * Tests whether the checks hasn't been run yet, then runs them and checks
   * that their lastRun value is not 0.
   */
  public function testRun() {

  }

  /**
   * Skips all checks then runs the checklist. No checks should be ran.
   */
  public function testSkippedRun() {

  }

}
