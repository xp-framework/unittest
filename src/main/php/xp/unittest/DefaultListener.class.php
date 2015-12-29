<?php namespace xp\unittest;

use unittest\TestListener;
use unittest\ColorizingListener;
use io\streams\OutputStreamWriter;
use lang\Runtime;

/**
 * Default listener
 * ----------------
 * Only shows details for failed tests. This listener has no options.
 */
class DefaultListener  implements TestListener, ColorizingListener {
  const OUTPUT_WIDTH= 72;

  public $out= null;
  protected $column= 0;
  private $colored= null;

  /**
   * Constructor
   *
   * @param   io.streams.OutputStreamWriter out
   */
  public function __construct(OutputStreamWriter $out) {
    $this->out= $out;
  }

  /**
   * Set color
   *
   * @param   bool color
   * @return  self
   */
  public function setColor($color) {
    if (null === $color) {
      $color= getenv('TERM') || getenv('ANSICON');
    }

    $this->colored= $color;
  }

  /**
   * Set color
   *
   * @param   bool color
   * @return  self
   */
  public function withColor($color) {
    $this->setColor($color);
    return $this;
  }

  /**
   * Output method; takes care of wrapping output if output line
   * exceeds maximum length
   *
   * @param   string string
   */
  protected function write($string) {
    if ($this->column > self::OUTPUT_WIDTH) {
      $this->out->writeLine();
      $this->column= 0;
    }

    $this->column++;
    $this->out->write($string);
  }

  /**
   * Called when a test case starts.
   *
   * @param   unittest.TestCase failure
   */
  public function testStarted(\unittest\TestCase $case) {
    // NOOP
  }

  /**
   * Called when a test fails.
   *
   * @param   unittest.TestFailure failure
   */
  public function testFailed(\unittest\TestFailure $failure) {
    $this->write('F');
  }

  /**
   * Called when a test errors.
   *
   * @param   unittest.TestError error
   */
  public function testError(\unittest\TestError $error) {
    $this->write('E');
  }

  /**
   * Called when a test raises warnings.
   *
   * @param   unittest.TestWarning warning
   */
  public function testWarning(\unittest\TestWarning $warning) {
    $this->write('W');
  }
  
  /**
   * Called when a test finished successfully.
   *
   * @param   unittest.TestSuccess success
   */
  public function testSucceeded(\unittest\TestSuccess $success) {
    $this->write('.');
  }
  
  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param   unittest.TestSkipped skipped
   */
  public function testSkipped(\unittest\TestSkipped $skipped) {
    $this->write('S');
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param   unittest.TestSkipped ignore
   */
  public function testNotRun(\unittest\TestSkipped $ignore) {
    $this->write('N');
  }

  /**
   * Called when a test run starts.
   *
   * @param   unittest.TestSuite suite
   */
  public function testRunStarted(\unittest\TestSuite $suite) {
    $this->write('[');
  }
  
  /**
   * Called when a test run finishes.
   *
   * @param  unittest.TestSuite $suite
   * @param  unittest.TestResult $result
   * @param  unittest.StopTests $stop
   */
  public function testRunFinished(\unittest\TestSuite $suite, \unittest\TestResult $result, \unittest\StopTests $stopped= null) {
    $failed= $result->failureCount();

    if ($stopped) {
      $this->out->writeLine('|');
      $indicator= ($this->colored ? "\033[43;1;30m■ " : 'STOP ').$stopped->getMessage();
    } else if ($failed) {
      $this->out->writeLine(']');
      $indicator= $this->colored ? "\033[41;1;37m✗" : 'FAIL';
    } else {
      $this->out->writeLine(']');
      $indicator= $this->colored ? "\033[42;1;37m✓" : 'OK';
    }

    // Show failed test details
    if ($failed) {
      $this->out->writeLine();
      foreach ($result->failed as $failure) {
        $this->out->writeLine('F ', $failure);
      }
    }

    $this->out->writeLinef(
      "\n%s: %d/%d run (%d skipped), %d succeeded, %d failed%s",
      $indicator,
      $result->runCount(),
      $result->count(),
      $result->skipCount(),
      $result->successCount(),
      $result->failureCount(),
      $this->colored ? "\033[0m" : ''
    );
    $this->out->writeLinef(
      'Memory used: %.2f kB (%.2f kB peak)',
      Runtime::getInstance()->memoryUsage() / 1024,
      Runtime::getInstance()->peakMemoryUsage() / 1024
    );
    $this->out->writeLinef(
      'Time taken: %.3f seconds',
      $result->elapsed()
    );
  }
}
