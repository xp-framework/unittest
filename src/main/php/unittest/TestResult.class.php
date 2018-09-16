<?php namespace unittest;

use lang\Value;
use unittest\metrics\MemoryUsed;
use unittest\metrics\Metric;
use unittest\metrics\TimeTaken;
use util\Objects;

/**
 * Test result
 *
 * @see   xp://unittest.TestSuite
 * @test  xp://unittest.tests.TestResultTest
 */
class TestResult implements Value {
  public
    $succeeded    = [],
    $failed       = [],
    $skipped      = [],
    $metrics      = [];

  /** Initializes metrics */
  public function __construct() {
    $this->metrics['Memory used']= new MemoryUsed();
    $this->metrics['Time taken']= new TimeTaken($this);
  }

  /**
   * Record outcome for a given test
   *
   * @param  unittest.TestOutcome $outcome
   * @return unittest.TestOutcome the given outcome
   */
  public function record(TestOutcome $outcome) {
    $key= $outcome->test()->hashCode();
    if ($outcome instanceof TestSuccess) {
      $this->succeeded[$key]= $outcome;
    } else if ($outcome instanceof TestSkipped) {
      $this->skipped[$key]= $outcome;
    } else if ($outcome instanceof TestFailure) {
      $this->failed[$key]= $outcome;
    }
    return $outcome;
  }

  /**
   * Returns the outcome of a specific test
   *
   * @param   unittest.TestCase test
   * @return  unittest.TestOutcome
   */
  public function outcomeOf(TestCase $test) {
    $key= $test->hashCode();
    foreach ([$this->succeeded, $this->failed, $this->skipped] as $lookup) {
      if (isset($lookup[$key])) return $lookup[$key];
    }
    return null;
  }

  /**
   * Set outcome for a given test
   *
   * @deprecated Use record() instead
   * @param   unittest.TestCase test
   * @param   unittest.TestOutcome outcome
   * @return  unittest.TestOutcome the given outcome
   */
  public function set(TestCase $test, TestOutcome $outcome) {
    return $this->record($outcome);
  }
  
  /**
   * Mark a test as succeeded
   *
   * @deprecated Use record() instead
   * @param   unittest.TestCase test
   * @param   float elapsed
   */
  public function setSucceeded($test, $elapsed) {
    return $this->succeeded[$test->hashCode()]= new TestExpectationMet($test, $elapsed);
  }
  
  /**
   * Mark a test as failed
   *
   * @deprecated Use record() instead
   * @param   unittest.TestCase test
   * @param   var reason
   * @param   float elapsed
   */
  public function setFailed($test, $reason, $elapsed) {
    return $this->failed[$test->hashCode()]= new TestAssertionFailed($test, $reason, $elapsed);
  }

  /**
   * Mark a test as been skipped
   *
   * @deprecated Use record() instead
   * @param   unittest.TestCase test
   * @param   var reason
   * @param   float elapsed
   * @return  unittest.TestSkipped s
   */
  public function setSkipped($test, $reason, $elapsed) {
    return $this->skipped[$test->hashCode()]= new TestPrerequisitesNotMet($test, $reason, $elapsed);
  }

  /**
   * Get number of succeeded tests
   *
   * @return  int
   */
  public function successCount() {
    return sizeof($this->succeeded);
  }
  
  /**
   * Get number of failed tests
   *
   * @return  int
   */
  public function failureCount() {
    return sizeof($this->failed);
  }

  /**
   * Get number of skipped tests
   *
   * @return  int
   */
  public function skipCount() {
    return sizeof($this->skipped);
  }

  /**
   * Get number of run tests (excluding skipped)
   *
   * @return  int
   */
  public function runCount() {
    return sizeof($this->succeeded) + sizeof($this->failed);
  }

  /**
   * Get number of total tests
   *
   * @return  int
   */
  public function count() {
    return sizeof($this->succeeded) + sizeof($this->failed) + sizeof($this->skipped);
  }

  /**
   * Returns elapsed time
   *
   * @return  float
   */
  public function elapsed() {
    $total= 0.0;
    foreach ($this->succeeded as $outcome) {
      $total+= $outcome->elapsed();
    }
    foreach ($this->failed as $outcome) {
      $total+= $outcome->elapsed();
    }
    foreach ($this->skipped as $outcome) {
      $total+= $outcome->elapsed();
    }
    return $total;
  }

  /**
   * Register a metric
   *
   * @param  string $name
   * @param  unttest.metrics.Metric
   * @return self
   */
  public function metric($name, Metric $metric) {
    $this->metrics[$name]= $metric;
    return $this;
  }

  /** @return [:function(): string] */
  public function metrics() { return $this->metrics; }
  
  /**
   * Create a nice string representation
   *
   * @return  string
   */
  public function toString() {
    $div= str_repeat('=', 72);
    $str= sprintf(
      "Results for test suite run at %s\n".
      "%d tests, %d succeeded, %d failed, %d skipped\n",
      date('r'),
      $this->count(),
      $this->successCount(),
      $this->failureCount(),
      $this->skipCount()
    );
    
    // Details
    if (!empty($this->succeeded)) {
      $str.= "\n- Succeeded tests details:\n";
      foreach (array_keys($this->succeeded) as $key) {
        $str.= '  * '.$this->succeeded[$key]->toString()."\n";
      }
    }
    if (!empty($this->skipped)) {
      $str.= "\n- Skipped tests details:\n";
      foreach (array_keys($this->skipped) as $key) {
        $str.= '  * '.$this->skipped[$key]->toString()."\n";
      }
    }
    if (!empty($this->failed)) {
      $str.= "\n- Failed tests details:\n";
      foreach (array_keys($this->failed) as $key) {
        $str.= '  * '.$this->failed[$key]->toString()."\n";
      }
    }
    return $str.$div."\n";
  }

  /** @return string */
  public function hashCode() {
    return Objects::hashOf([$this->succeeded, $this->failed, $this->skipped]);
  }

  /**
   * Compares this test outcome to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    if (!($value instanceof self)) return 1;

    return Objects::compare(
      [$this->succeeded, $this->failed, $this->skipped],
      [$value->succeeded, $value->failed, $this->skipped]
    );
  }
}
