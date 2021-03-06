<?php namespace unittest\tests;

use lang\IllegalArgumentException;
use unittest\{Listener, PrerequisitesNotMetError, Test, TestAssertionFailed, TestCase, TestError, TestExpectationMet, TestFailure, TestNotRun, TestPrerequisitesNotMet, TestResult, TestSkipped, TestStart, TestSuccess, TestSuite, TestWarning};

class ListenerTest extends TestCase implements Listener {
  private $suite, $invocations;
    
  /** @return void */
  public function setUp() {
    $this->invocations= [];
    $this->suite= new TestSuite();
  }

  /** @return void */
  public function tearDown() {
    $this->suite->removeListener($this);
  }

  /**
   * Called when a test case starts.
   *
   * @param   unittest.TestStart $start
   */
  public function testStarted(TestStart $start) {
    $this->invocations[__FUNCTION__]= [$start];
  }

  /**
   * Called when a test fails.
   *
   * @param   unittest.TestFailure failure
   */
  public function testFailed(TestFailure $failure) {
    $this->invocations[__FUNCTION__]= [$failure];
  }

  /**
   * Called when a test errors.
   *
   * @param   unittest.TestFailure error
   */
  public function testError(TestError $error) {
    $this->invocations[__FUNCTION__]= [$error];
  }

  /**
   * Called when a test raises warnings.
   *
   * @param   unittest.TestWarning warning
   */
  public function testWarning(TestWarning $warning) {
    $this->invocations[__FUNCTION__]= [$warning];
  }

  /**
   * Called when a test finished successfully.
   *
   * @param   unittest.TestSuccess success
   */
  public function testSucceeded(TestSuccess $success) {
    $this->invocations[__FUNCTION__]= [$success];
  }

  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param   unittest.TestSkipped skipped
   */
  public function testSkipped(TestSkipped $skipped) {
    $this->invocations[__FUNCTION__]= [$skipped];
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param   unittest.TestSkipped ignore
   */
  public function testNotRun(TestSkipped $ignore) {
    $this->invocations[__FUNCTION__]= [$ignore];
  }

  /**
   * Called when a test run starts.
   *
   * @param   unittest.TestSuite suite
   */
  public function testRunStarted(TestSuite $suite) {
    $this->invocations[__FUNCTION__]= [$suite];
  }

  /**
   * Called when a test run finishes.
   *
   * @param   unittest.TestSuite suite
   * @param   unittest.TestResult result
   */
  public function testRunFinished(TestSuite $suite, TestResult $result) {
    $this->invocations[__FUNCTION__]= [$suite, $result];
  }

  #[Test]
  public function add_listener() {
    $this->assertEquals($this, $this->suite->addListener($this));
  }

  #[Test]
  public function remove_listener() {
    $this->suite->addListener($this);
    $this->assertTrue($this->suite->removeListener($this));
  }

  #[Test]
  public function remove_non_existant_listener() {
    $this->assertFalse($this->suite->removeListener($this));
  }

  #[Test]
  public function string_representation() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { /** NOOP */ }
    ]));
    $this->assertNotEquals('', $this->suite->toString());
  }

  #[Test]
  public function hash_code() {
    $this->assertNotEquals('', $this->suite->hashCode());
  }

  #[Test]
  public function notifiedOnSuccess() {
    $case= newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(true); }
    ]);
    $this->suite->addListener($this);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertInstanceOf(TestStart::class, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf(TestExpectationMet::class, $this->invocations['testSucceeded'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf(TestResult::class, $this->invocations['testRunFinished'][1]);
  }    

  #[Test]
  public function notifiedOnFailure() {
    $case= newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(false); }
    ]);
    $this->suite->addListener($this);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertInstanceOf(TestStart::class, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf(TestAssertionFailed::class, $this->invocations['testFailed'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf(TestResult::class, $this->invocations['testRunFinished'][1]);
  }    

  #[Test]
  public function notifiedOnException() {
    $case= newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { throw new IllegalArgumentException('Test'); }
    ]);
    $this->suite->addListener($this);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertInstanceOf(TestStart::class, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf(TestError::class, $this->invocations['testError'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf(TestResult::class, $this->invocations['testRunFinished'][1]);
  }    

  #[Test]
  public function notifiedOnError() {
    $case= newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { trigger_error('Test error'); }
    ]);
    $this->suite->addListener($this);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertInstanceOf(TestStart::class, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf(TestWarning::class, $this->invocations['testWarning'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf(TestResult::class, $this->invocations['testRunFinished'][1]);
  }    

  #[Test]
  public function notifiedOnSkipped() {
    $case= newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new PrerequisitesNotMetError('SKIP', null, $this->name); },
      '#[Test] fixture' => function() { /* Intentionally empty */ }
    ]);
    $this->suite->addListener($this);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertInstanceOf(TestStart::class, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf(TestPrerequisitesNotMet::class, $this->invocations['testSkipped'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf(TestResult::class, $this->invocations['testRunFinished'][1]);
  }    

  #[Test]
  public function notifiedOnIgnored() {
    $case= newinstance(TestCase::class, ['fixture'], [
      '#[Test, Ignore] fixture' => function() { /* Intentionally empty */ }
    ]);
    $this->suite->addListener($this);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertInstanceOf(TestStart::class, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf(TestNotRun::class, $this->invocations['testNotRun'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf(TestResult::class, $this->invocations['testRunFinished'][1]);
  }    
}