<?php namespace unittest\tests;

use unittest\Test;
use unittest\actions\ExtensionAvailable;

/**
 * Test test action "Extension Available"
 */
class ExtensionAvailableTest extends \unittest\TestCase {

  #[Test]
  public function can_create() {
    new ExtensionAvailable('standard');
  }

  #[Test]
  public function verify_standard_extension() {
    $this->assertTrue((new ExtensionAvailable('standard'))->verify());
  }

  #[Test]
  public function verify_non_existant_extension() {
    $this->assertFalse((new ExtensionAvailable('@@non-existant@@'))->verify());
  }
}