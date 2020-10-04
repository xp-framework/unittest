<?php namespace unittest\tests;

use unittest\actions\IsPlatform;
use unittest\{Test, Values};

/**
 * Test test action "Is Platform"
 */
class IsPlatformTest extends \unittest\TestCase {

  #[Test]
  public function can_create() {
    new IsPlatform('Windows');
  }

  #[Test]
  public function verify_current_platform() {
    $this->assertTrue((new IsPlatform('*'))->verify());
  }

  #[Test]
  public function verify_platform_with_same_name_as_os() {
    $this->assertTrue((new IsPlatform('Windows'))->verify('Windows'));
  }

  #[Test]
  public function verify_platform_case_insensitively() {
    $this->assertTrue((new IsPlatform('WINDOWS'))->verify('Windows'));
  }

  #[Test, Values(['WinNT', 'Windows', 'Windows 8.1'])]
  public function verify_platform_matching_leading_segment($value) {
    $this->assertTrue((new IsPlatform('WIN'))->verify($value));
  }

  #[Test]
  public function verify_platform_substring() {
    $this->assertFalse((new IsPlatform('DOW'))->verify('Windows'));
  }

  #[Test, Values(['Linux', 'MacOS', 'Un*x'])]
  public function verify_platform_with_different_name($value) {
    $this->assertFalse((new IsPlatform('Windows'))->verify($value));
  }

  #[Test, Values(['Linux', 'MacOS', 'Un*x'])]
  public function negative_verify_platform_with_different_name($value) {
    $this->assertTrue((new IsPlatform('!Windows'))->verify($value));
  }

  #[Test]
  public function negative_verify_platform_with_same_name() {
    $this->assertFalse((new IsPlatform('!Windows'))->verify('Windows'));
  }

  #[Test, Values(['Windows', 'MacOS', 'Un*x'])]
  public function verify_platform_selection_negatively($value) {
    $this->assertTrue((new IsPlatform('!*BSD'))->verify($value));
  }

  #[Test, Values(['FreeBSD', 'OpenBSD'])]
  public function verify_platform_selection($value) {
    $this->assertTrue((new IsPlatform('*BSD'))->verify($value));
  }

  #[Test, Values(['FreeBSD', 'OpenBSD'])]
  public function verify_platform_alternatively($value) {
    $this->assertTrue((new IsPlatform('FreeBSD|OpenBSD'))->verify($value));
  }
}