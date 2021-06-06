<?php namespace xp\unittest;

use io\streams\OutputStreamWriter;
use unittest\{Test, Listener};

/**
 * Colorful verbose test listener
 * 
 * Features:
 * - Updates console with background-colored bar: blue while running
 *   successfully, red if any error has occurred, green when all tests
 *   finished successfully.
 * - Recycles status line
 * - Fast feedback when a TestFailure occurrs (writes stacktrace / test
 *   information out instantly)
 *
 */
class ColoredBarListener implements Listener {
  const PROGRESS_WIDTH = 10;
  const CODE_RED       = '41;1;37';
  const CODE_GREEN     = '42;1;37';
  const CODE_BLUE      = '44;1;37';

  private $out= null;
  private $cur, $sum, $status;
  private $stats;

  /**
   * Constructor
   *
   * @param   io.streams.OutputStreamWriter out
   */
  public function __construct(OutputStreamWriter $out) {
    $this->out= $out;
  }

  /**
   * Write status of currently executing test case
   *
   * @param  unittest.Test $test
   * @return void
   */
  private function writeStatus(Test $test= null) {
    if (null === $test) {
      $this->cur= $this->sum;
      $done= self::PROGRESS_WIDTH;
      $color= $this->status ? self::CODE_GREEN : self::CODE_RED;
    } else {
      $this->cur++;
      $done= floor($this->cur / $this->sum * self::PROGRESS_WIDTH);
      $color= self::CODE_BLUE;
    }
  
    $out= sprintf('Running %-3d of %d ▐%s▌ %01dF %01dE %01dW %01dS %01dN',
      $this->cur,
      $this->sum,
      str_repeat('█', $done).str_repeat(' ', self::PROGRESS_WIDTH - $done),
      $this->stats['failed'],
      $this->stats['errored'],
      $this->stats['warned'],
      $this->stats['skipped'],
      $this->stats['notrun']
    );

    // Format output so it's 72 characters wide (8 for status, 2 spaces padding)
    $this->out->writef(
      "\r\033[%sm  %s%s▌ %s  \033[0m",
      $color,
      $out,
      str_repeat(' ', 60 - iconv_strlen($out, 'utf-8')),
      $this->status ? 'PASSING' : 'FAILURE!'
    );
  }

  /**
   * Write test failure
   *
   * @param   unittest.TestOutcome result
   */
  private function writeFailure(\unittest\TestOutcome $result) {
    $this->out->write("\r");
    $this->out->writeLine($result);
    $this->out->writeLine();
  }

  /**
   * Write colored line
   *
   * @param   string line
   * @param   string code
   */
  private function writeColoredLine($line, $code) {
    $this->len= strlen($line);
    $this->out->write($code.$line.self::$CODE_RESET);
  }

  /**
   * Called when a test case starts.
   *
   * @param   unittest.TestStart $start
   */
  public function testStarted(\unittest\TestStart $start) {
    $this->writeStatus($start->test());
  }

  /**
   * Called when a test fails.
   *
   * @param   unittest.TestFailure failure
   */
  public function testFailed(\unittest\TestFailure $failure) {
    $this->status= false;
    $this->stats['failed']++;
    $this->writeFailure($failure);
  }

  /**
   * Called when a test errors.
   *
   * @param   unittest.TestError error
   */
  public function testError(\unittest\TestError $error) {
    $this->status= false;
    $this->stats['errored']++;
    $this->writeFailure($error);
  }

  /**
   * Called when a test raises warnings.
   *
   * @param   unittest.TestWarning warning
   */
  public function testWarning(\unittest\TestWarning $warning) {
    $this->writeFailure($warning);
    $this->stats['warned']++;
  }

  /**
   * Called when a test finished successfully.
   *
   * @param   unittest.TestSuccess success
   */
  public function testSucceeded(\unittest\TestSuccess $success) {
  }

  /**
   * Called when a test is not run because it is skipped due to a
   * failed prerequisite.
   *
   * @param   unittest.TestSkipped skipped
   */
  public function testSkipped(\unittest\TestSkipped $skipped) {
    $this->stats['skipped']++;
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param   unittest.TestSkipped ignore
   */
  public function testNotRun(\unittest\TestSkipped $ignore) {
    $this->stats['notrun']++;
  }

  /**
   * Called when a test run starts.
   *
   * @param   unittest.TestSuite suite
   */
  public function testRunStarted(\unittest\TestSuite $suite) {
    $this->sum= $suite->numTests();
    $this->cur= 0;
    $this->stats= [
      'failed'  => 0,
      'errored' => 0,
      'warned'  => 0,
      'skipped' => 0,
      'notrun'  => 0
    ];
    $this->status= true;
  }

  /**
   * Called when a test run finishes.
   *
   * @param   unittest.TestSuite suite
   * @param   unittest.TestResult result
   */
  public function testRunFinished(\unittest\TestSuite $suite, \unittest\TestResult $result) {
    $this->writeStatus();
    $this->out->writeLine();

    // Summary output
    $this->out->writeLinef(
      "\n%s: %d/%d run (%d skipped), %d succeeded, %d failed",
      $result->failureCount() > 0 ? 'FAIL' : 'OK',
      $result->runCount(),
      $result->count(),
      $result->skipCount(),
      $result->successCount(),
      $result->failureCount()
    );
    foreach ($result->metrics() as $name => $metric) {
      $this->out->writeLine($name, ': ', $metric->formatted());
    }
  }
}