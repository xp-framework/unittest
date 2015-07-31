<?php namespace unittest\tests;

use unittest\actions\IsPlatform;

/**
 * Test test action "Is Platform"
 */
class IsPlatformTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new IsPlatform('Windows');
  }

  #[@test]
  public function verify_current_platform() {
    $this->assertTrue((new IsPlatform('*'))->verify());
  }

  #[@test]
  public function verify_platform_with_same_name_as_os() {
    $this->assertTrue((new IsPlatform('Windows'))->verify('Windows'));
  }

  #[@test]
  public function verify_platform_case_insensitively() {
    $this->assertTrue((new IsPlatform('WINDOWS'))->verify('Windows'));
  }

  #[@test, @values(['WinNT', 'Windows', 'Windows 8.1'])]
  public function verify_platform_matching_leading_segment($value) {
    $this->assertTrue((new IsPlatform('WIN'))->verify($value));
  }

  #[@test]
  public function verify_platform_substring() {
    $this->assertFalse((new IsPlatform('DOW'))->verify('Windows'));
  }

  #[@test, @values(['Linux', 'MacOS', 'Un*x'])]
  public function verify_platform_with_different_name($value) {
    $this->assertFalse((new IsPlatform('Windows'))->verify($value));
  }

  #[@test, @values(['Linux', 'MacOS', 'Un*x'])]
  public function negative_verify_platform_with_different_name($value) {
    $this->assertTrue((new IsPlatform('!Windows'))->verify($value));
  }

  #[@test]
  public function negative_verify_platform_with_same_name() {
    $this->assertFalse((new IsPlatform('!Windows'))->verify('Windows'));
  }

  #[@test, @values(['Windows', 'MacOS', 'Un*x'])]
  public function verify_platform_selection_negatively($value) {
    $this->assertTrue((new IsPlatform('!*BSD'))->verify($value));
  }

  #[@test, @values(['FreeBSD', 'OpenBSD'])]
  public function verify_platform_selection($value) {
    $this->assertTrue((new IsPlatform('*BSD'))->verify($value));
  }

  #[@test, @values(['FreeBSD', 'OpenBSD'])]
  public function verify_platform_alternatively($value) {
    $this->assertTrue((new IsPlatform('FreeBSD|OpenBSD'))->verify($value));
  }
}