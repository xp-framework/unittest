<?php namespace unittest\tests;

use unittest\TestSuite;

/**
 * Test test class actions
 */
class TestClassActionTest extends \unittest\TestCase {
  protected $suite= null;

  /**
   * Setup method. Creates a new test suite.
   */
  public function setUp() {
    $this->suite= new TestSuite();
  }

  #[@test]
  public function beforeTestClass_and_afterTestClass_invocation_order() {
    $this->suite->runTest(new TestWithClassAction('fixture'));
    $this->assertEquals(['before', 'test', 'after'], TestWithClassAction::$run);
  }
}