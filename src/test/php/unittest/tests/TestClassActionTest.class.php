<?php namespace unittest\tests;

use unittest\{Test, TestSuite};

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

  #[Test]
  public function beforeTestClass_and_afterTestClass_invocation_order() {
    TestWithClassAction::$run= [];
    $this->suite->runTest(new TestWithClassAction('fixture'));
    $this->assertEquals(['before', 'test', 'after'], TestWithClassAction::$run);
  }
}