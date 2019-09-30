<?php namespace xp\unittest;

use io\streams\OutputStreamWriter;
use unittest\{TestCase, TestListener};

/**
 * Verbose listener
 * ----------------
 * Shows details for all tests (succeeded, failed and skipped/ignored).
 * This listener has no options.
 */
class VerboseListener implements TestListener {
  public $out= null;
  
  /**
   * Constructor
   *
   * @param   io.streams.OutputStreamWriter out
   */
  public function __construct(OutputStreamWriter $out) {
    $this->out= $out;
  }

  /**
   * Called when a test case starts.
   *
   * @param   unittest.TestCase failure
   */
  public function testStarted(TestCase $case) {
    // NOOP
  }

  /**
   * Called when a test fails.
   *
   * @param   unittest.TestFailure failure
   */
  public function testFailed(\unittest\TestFailure $failure) {
    $this->out->write('F');
  }

  /**
   * Called when a test errors.
   *
   * @param   unittest.TestError error
   */
  public function testError(\unittest\TestError $error) {
    $this->out->write('E');
  }

  /**
   * Called when a test raises warnings.
   *
   * @param   unittest.TestWarning warning
   */
  public function testWarning(\unittest\TestWarning $warning) {
    $this->out->write('W');
  }
  
  /**
   * Called when a test finished successfully.
   *
   * @param   unittest.TestSuccess success
   */
  public function testSucceeded(\unittest\TestSuccess $success) {
    $this->out->write('.');
  }
  
  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param   unittest.TestSkipped skipped
   */
  public function testSkipped(\unittest\TestSkipped $skipped) {
    $this->out->write('S');
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param   unittest.TestSkipped ignore
   */
  public function testNotRun(\unittest\TestSkipped $ignore) {
    $this->out->write('N');
  }

  /**
   * Called when a test run starts.
   *
   * @param   unittest.TestSuite suite
   */
  public function testRunStarted(\unittest\TestSuite $suite) {
    $this->out->writeLine('===> Running test suite (', $suite->numTests(), ' test(s))');
  }
  
  /**
   * Called when a test run finishes.
   *
   * @param   unittest.TestSuite suite
   * @param   unittest.TestResult result
   * @param  unittest.StopTests $stop
   */
  public function testRunFinished(\unittest\TestSuite $suite, \unittest\TestResult $result, \unittest\StopTests $stopped= null) {

    // Details
    if ($result->successCount() > 0) {
      $this->out->writeLine("\n---> Succeeeded:");
      foreach (array_keys($result->succeeded) as $key) {
        $this->out->writeLine('* ', $result->succeeded[$key]);
      }
    }
    if ($result->skipCount() > 0) {
      $this->out->writeLine("\n---> Skipped:");
      foreach (array_keys($result->skipped) as $key) {
        $this->out->writeLine('* ', $result->skipped[$key]);
      }
    }
    if ($result->failureCount() > 0) {
      $this->out->writeLine("\n---> Failed:");
      foreach (array_keys($result->failed) as $key) {
        $this->out->writeLine('* ', $result->failed[$key]);
      }
    }

    $this->out->writeLinef(
      "\n===> %s: %d run (%d skipped), %d succeeded, %d failed",
      $stopped ? 'STOP '.$stopped->getMessage() : ($result->failureCount() ? 'FAIL' : 'OK'),
      $result->runCount(),
      $result->skipCount(),
      $result->successCount(),
      $result->failureCount()
    );
    foreach ($result->metrics() as $name => $metric) {
      $this->out->writeLine('===> ', $name, ': ', $metric->formatted());
    }
  }
}