<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Role testing for demo module.
 *
 * @group minisite
 */
class DemoRoleTest extends BrowserTestBase {

  /**
   * Test that the Demorole role is present.
   */
  public function testRolePresent() {
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/people/roles');
    $this->assertText('Authenticated user');
  }

}
