<?php

namespace Drupal\adv_audit\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Contains tests for Check that don't suffice with KernelTestBase.
 *
 * @group adv_audit
 */
class CheckWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['adv_audit'];

  /**
   * The security checks defined by Security Review.
   *
   * @var \Drupal\adv_audit\Check[]
   */
  protected $checks;

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Sets up the testing environment, logs the user in, populates $check.
   */
  protected function setUp() {
    parent::setUp();

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

    // Get checks.

  }

  /**
   * Tests Check::skip().
   *
   * Checks whether skip() marks the check as skipped, and checks the
   * skippedBy() value.
   */
  public function testSkipCheck() {
    foreach ($this->checks as $check) {
      $check->skip();

      $is_skipped = $check->isSkipped();
      $skipped_by = $check->skippedBy();

      $this->assertTrue($is_skipped, $check->getTitle() . ' skipped.');
      $this->assertEqual($this->user->id(), $skipped_by->id(), 'Skipped by ' . $skipped_by->label());
    }
  }

}
