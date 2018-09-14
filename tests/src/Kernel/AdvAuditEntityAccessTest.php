<?php

namespace Drupal\Tests\adv_audit\Kernel;

use Drupal\adv_audit\Entity\AdvAuditEntity;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests for the AdvAuditEntity access control.
 *
 * @group adv_audit
 */
class AdvAuditEntityAccessTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['adv_audit', 'system', 'user'];

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * The AdvAuditEntity object used in the test.
   *
   * @var \Drupal\adv_audit\Entity\AdvAuditEntityInterface
   */
  protected $advAuditEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->user1 = User::create([
      'name' => 'user1',
      'status' => 1,
    ]);
    $this->user1->save();

    $this->user2 = User::create([
      'name' => 'user2',
      'status' => 1,
    ]);
    $this->user2->save();

    $this->advAuditEntity = AdvAuditEntity::create([
      'uid' => $this->user1->id(),
      'name' => 'test',
    ]);
  }

  /**
   * Tests that only the entity owner can view, delete or update an entity.
   */
  public function testOnlyOwnerCanDeleteUpdateResult() {
    \Drupal::currentUser()->setAccount($this->user2);
    $this->assertFalse($this->advAuditEntity->access('delete'));
    $this->assertFalse($this->advAuditEntity->access('update'));
    $this->assertFalse($this->advAuditEntity->access('view'));

    \Drupal::currentUser()->setAccount($this->user1);
    $this->assertTrue($this->advAuditEntity->access('delete'));
    $this->assertTrue($this->advAuditEntity->access('update'));
    $this->assertTrue($this->advAuditEntity->access('view'));
  }

}
