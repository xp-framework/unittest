<?php namespace xp\unittest;

use unittest\TestCase;
use unittest\TestSuite;
use unittest\TestResult;
use unittest\TestFailure;
use unittest\TestError;
use unittest\TestWarning;
use unittest\TestSuccess;
use unittest\TestSkipped;
use unittest\StopTests;

/**
 * Stop listener
 * -------------
 * Checks for given events and stops the run
 */
class StopListener implements \unittest\TestListener {
  const FAIL   = 0x0001;
  const SKIP   = 0x0002;
  const IGNORE = 0x0004;

  private $events;

  /**
   * Stop on certain events
   *
   * @param  int $events Bitfield of FAIL, SKIP and IGNORE constants
   */
  public function __construct($events) {
    $this->events= $events;
  }

  /**
   * Called when a test case starts.
   *
   * @param  unittest.TestCase $case
   */
  public function testStarted(TestCase $case) {
    // NOOP
  }

  /**
   * Called when a test fails.
   *
   * @param  unittest.TestFailure $failure
   */
  public function testFailed(TestFailure $failure) {
    if ($this->events & self::FAIL) {
      throw new StopTests($failure->reason);
    }
  }

  /**
   * Called when a test errors.
   *
   * @param  unittest.TestError $error
   */
  public function testError(TestError $error) {
    if ($this->events & self::FAIL) {
      throw new StopTests($error->reason);
    }
  }

  /**
   * Called when a test raises warnings.
   *
   * @param  unittest.TestWarning $warning
   */
  public function testWarning(TestWarning $warning) {
    if ($this->events & self::FAIL) {
      throw new StopTests($warning->reason);
    }
  }
  
  /**
   * Called when a test finished successfully.
   *
   * @param  unittest.TestSuccess $success
   */
  public function testSucceeded(TestSuccess $success) {
    // NOOP
  }
  
  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param  unittest.TestSkipped $skipped
   */
  public function testSkipped(TestSkipped $skipped) {
    if ($this->events & self::SKIP) {
      throw new StopTests($skipped->reason);
    }
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param  unittest.TestSkipped $ignore
   */
  public function testNotRun(TestSkipped $ignore) {
    if ($this->events & self::IGNORE) {
      throw new StopTests($ignore->reason);
    }
  }

  /**
   * Called when a test run starts.
   *
   * @param  unittest.TestSuite $suite
   */
  public function testRunStarted(TestSuite $suite) {
    // NOOP
  }
  
  /**
   * Called when a test run finishes.
   *
   * @param  unittest.TestSuite $suite
   * @param  unittest.TestResult $result
   * @param  unittest.StopTests $stop
   */
  public function testRunFinished(TestSuite $suite, TestResult $result, StopTests $stop= null) {
    // NOOP
  }
}
