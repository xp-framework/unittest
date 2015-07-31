<?php namespace unittest\tests;

use unittest\TestCase;
use unittest\TestSuite;
use unittest\PrerequisitesNotMetError;
use util\collections\HashTable;
use lang\types\ArrayList;
use lang\IllegalArgumentException;

/**
 * TestCase
 *
 * @see   xp://unittest.TestListener
 */
class ListenerTest extends TestCase implements \unittest\TestListener {
  private $suite, $invocations;
    
  /** @return void */
  public function setUp() {
    $this->invocations= create('new util.collections.HashTable<string, lang.types.ArrayList>()');
    $this->suite= new TestSuite();
    $this->suite->addListener($this);
  }

  /**
   * Remove listener again at tearDown.
   */
  public function tearDown() {
    $this->suite->removeListener($this);
  }
  
  /**
   * Called when a test case starts.
   *
   * @param   unittest.TestCase failure
   */
  public function testStarted(TestCase $case) {
    $this->invocations[__FUNCTION__]= new ArrayList($case);
  }

  /**
   * Called when a test fails.
   *
   * @param   unittest.TestFailure failure
   */
  public function testFailed(\unittest\TestFailure $failure) {
    $this->invocations[__FUNCTION__]= new ArrayList($failure);
  }

  /**
   * Called when a test errors.
   *
   * @param   unittest.TestFailure error
   */
  public function testError(\unittest\TestError $error) {
    $this->invocations[__FUNCTION__]= new ArrayList($error);
  }

  /**
   * Called when a test raises warnings.
   *
   * @param   unittest.TestWarning warning
   */
  public function testWarning(\unittest\TestWarning $warning) {
    $this->invocations[__FUNCTION__]= new ArrayList($warning);
  }

  /**
   * Called when a test finished successfully.
   *
   * @param   unittest.TestSuccess success
   */
  public function testSucceeded(\unittest\TestSuccess $success) {
    $this->invocations[__FUNCTION__]= new ArrayList($success);
  }

  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param   unittest.TestSkipped skipped
   */
  public function testSkipped(\unittest\TestSkipped $skipped) {
    $this->invocations[__FUNCTION__]= new ArrayList($skipped);
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param   unittest.TestSkipped ignore
   */
  public function testNotRun(\unittest\TestSkipped $ignore) {
    $this->invocations[__FUNCTION__]= new ArrayList($ignore);
  }

  /**
   * Called when a test run starts.
   *
   * @param   unittest.TestSuite suite
   */
  public function testRunStarted(TestSuite $suite) {
    $this->invocations[__FUNCTION__]= new ArrayList($suite);
  }

  /**
   * Called when a test run finishes.
   *
   * @param   unittest.TestSuite suite
   * @param   unittest.TestResult result
   */
  public function testRunFinished(TestSuite $suite, \unittest\TestResult $result) {
    $this->invocations[__FUNCTION__]= new ArrayList($suite, $result);
  }

  #[@test]
  public function notifiedOnSuccess() {
    $case= newinstance('unittest.TestCase', ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(true); }
    ]);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertEquals($case, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf('unittest.TestExpectationMet', $this->invocations['testSucceeded'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf('unittest.TestResult', $this->invocations['testRunFinished'][1]);
  }    

  #[@test]
  public function notifiedOnFailure() {
    $case= newinstance('unittest.TestCase', ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(false); }
    ]);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertEquals($case, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf('unittest.TestAssertionFailed', $this->invocations['testFailed'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf('unittest.TestResult', $this->invocations['testRunFinished'][1]);
  }    

  #[@test]
  public function notifiedOnException() {
    $case= newinstance('unittest.TestCase', ['fixture'], [
      '#[@test] fixture' => function() { throw new IllegalArgumentException('Test'); }
    ]);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertEquals($case, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf('unittest.TestError', $this->invocations['testError'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf('unittest.TestResult', $this->invocations['testRunFinished'][1]);
  }    

  #[@test]
  public function notifiedOnError() {
    $case= newinstance('unittest.TestCase', ['fixture'], [
      '#[@test] fixture' => function() { trigger_error('Test error'); }
    ]);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertEquals($case, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf('unittest.TestWarning', $this->invocations['testWarning'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf('unittest.TestResult', $this->invocations['testRunFinished'][1]);
  }    

  #[@test]
  public function notifiedOnSkipped() {
    $case= newinstance('unittest.TestCase', ['fixture'], [
      'setUp' => function() { throw new PrerequisitesNotMetError('SKIP', null, $this->name); },
      '#[@test] fixture' => function() { /* Intentionally empty */ }
    ]);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertEquals($case, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf('unittest.TestPrerequisitesNotMet', $this->invocations['testSkipped'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf('unittest.TestResult', $this->invocations['testRunFinished'][1]);
  }    

  #[@test]
  public function notifiedOnIgnored() {
    $case= newinstance('unittest.TestCase', ['fixture'], [
      '#[@test, @ignore] fixture' => function() { /* Intentionally empty */ }
    ]);
    $this->suite->runTest($case);
    $this->assertEquals($this->suite, $this->invocations['testRunStarted'][0]);
    $this->assertEquals($case, $this->invocations['testStarted'][0]);
    $this->assertInstanceOf('unittest.TestNotRun', $this->invocations['testNotRun'][0]);
    $this->assertEquals($this->suite, $this->invocations['testRunFinished'][0]);
    $this->assertInstanceOf('unittest.TestResult', $this->invocations['testRunFinished'][1]);
  }    
}
