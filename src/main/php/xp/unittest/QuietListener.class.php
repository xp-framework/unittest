<?php namespace xp\unittest;

use io\streams\OutputStreamWriter;
use unittest\{Listener, TestStart};

/**
 * Quiet listener
 * --------------
 * No output at all. This listener has no options.
 */
class QuietListener implements Listener {

  /**
   * Constructor
   *
   * @param   io.streams.OutputStreamWriter out
   */
  public function __construct(OutputStreamWriter $out) {
    // NOOP
  }

  /**
   * Called when a test case starts.
   *
   * @param  unittest.TestStart $start
   */
  public function testStarted(TestStart $start) {
    // NOOP
  }

  /**
   * Called when a test fails.
   *
   * @param   unittest.TestFailure failure
   */
  public function testFailed(\unittest\TestFailure $failure) {
    // NOOP
  }

  /**
   * Called when a test errors.
   *
   * @param   unittest.TestError error
   */
  public function testError(\unittest\TestError $error) {
    // NOOP
  }

  /**
   * Called when a test raises warnings.
   *
   * @param   unittest.TestWarning warning
   */
  public function testWarning(\unittest\TestWarning $warning) {
    // NOOP
  }
  
  /**
   * Called when a test finished successfully.
   *
   * @param   unittest.TestSuccess success
   */
  public function testSucceeded(\unittest\TestSuccess $success) {
    // NOOP
  }
  
  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param   unittest.TestSkipped skipped
   */
  public function testSkipped(\unittest\TestSkipped $skipped) {
    // NOOP
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param   unittest.TestSkipped ignore
   */
  public function testNotRun(\unittest\TestSkipped $ignore) {
    // NOOP
  }

  /**
   * Called when a test run starts.
   *
   * @param   unittest.TestSuite suite
   */
  public function testRunStarted(\unittest\TestSuite $suite) {
    // NOOP
  }
  
  /**
   * Called when a test run finishes.
   *
   * @param   unittest.TestSuite suite
   * @param   unittest.TestResult result
   */
  public function testRunFinished(\unittest\TestSuite $suite, \unittest\TestResult $result) {
    // NOOP
  }
}