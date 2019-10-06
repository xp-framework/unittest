<?php namespace unittest;

class ListenerAdapter implements Listener {
  private $listener;

  /** Creates a new adapter */
  public function __construct(TestListener $listener) {
    $this->listener= $listener;
  }

  /**
   * Called when a test case starts.
   *
   * @param  unittest.TestStart $start
   */
  public function testStarted(TestStart $start) {
    $test= $start->test();
    if ($test instanceof TestCaseInstance) {
      $this->listener->testStarted($test->instance);
    } else {
      $name= $test->getName();
      return newinstance(TestCase::class, [$name], [
        $name => function() use($test) {
          $test->method->invoke($test->instance, []);
        }
      ]);
    }
  }

  /**
   * Called when a test fails.
   *
   * @param  unittest.TestFailure $failure
   */
  public function testFailed(TestFailure $failure) {
    $this->listener->testFailed($failure);
  }

  /**
   * Called when a test errors.
   *
   * @param  unittest.TestFailure $error
   */
  public function testError(TestError $error) {
    $this->listener->testError($error);
  }

  /**
   * Called when a test raises warnings.
   *
   * @param  unittest.TestWarning $warning
   */
  public function testWarning(TestWarning $warning) {
    $this->listener->testWarning($warning);
  }
  
  /**
   * Called when a test finished successfully.
   *
   * @param  unittest.TestSuccess $success
   */
  public function testSucceeded(TestSuccess $success) {
    $this->listener->testSucceeded($success);
  }

  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param  unittest.TestSkipped $skipped
   */
  public function testSkipped(TestSkipped $skipped) {
    $this->listener->testSkipped($skipped);
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the `ignore` annotation.
   *
   * @param  unittest.TestSkipped $ignore
   */
  public function testNotRun(TestSkipped $ignore) {
    $this->listener->testNotRun($ignore);
  }

  /**
   * Called when a test run starts.
   *
   * @param  unittest.TestSuite $suite
   */
  public function testRunStarted(TestSuite $suite) {
    $this->listener->testRunStarted($suite);
  }
  
  /**
   * Called when a test run finishes.
   *
   * @param  unittest.TestSuite $suite
   * @param  unittest.TestResult $result
   */
  public function testRunFinished(TestSuite $suite, TestResult $result) {
    $this->listener->testRunFinished($suite, $result);
  }
}