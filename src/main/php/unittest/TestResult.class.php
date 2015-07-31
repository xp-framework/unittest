<?php namespace unittest;

/**
 * Test result
 *
 * @see   xp://unittest.TestSuite
 */
class TestResult extends \lang\Object {
  public
    $succeeded    = [],
    $failed       = [],
    $skipped      = [];
    
  /**
   * Set outcome for a given test
   *
   * @param   unittest.TestCase test
   * @param   unittest.TestOutcome outcome
   * @return  unittest.TestOutcome the given outcome
   */
  public function set(TestCase $test, TestOutcome $outcome) {
    if ($outcome instanceof TestSuccess) {
      $this->succeeded[$test->hashCode()]= $outcome;
    } else if ($outcome instanceof TestSkipped) {
      $this->skipped[$test->hashCode()]= $outcome;
    } else if ($outcome instanceof TestFailure) {
      $this->failed[$test->hashCode()]= $outcome;
    }
    return $outcome;
  }
  
  /**
   * Mark a test as succeeded
   *
   * @param   unittest.TestCase test
   * @param   float elapsed
   */
  public function setSucceeded($test, $elapsed) {
    return $this->succeeded[$test->hashCode()]= new TestExpectationMet($test, $elapsed);
  }
  
  /**
   * Mark a test as failed
   *
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
   * @param   unittest.TestCase test
   * @param   var reason
   * @param   float elapsed
   * @return  unittest.TestSkipped s
   */
  public function setSkipped($test, $reason, $elapsed) {
    return $this->skipped[$test->hashCode()]= new TestPrerequisitesNotMet($test, $reason, $elapsed);
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
    return sizeof($this->succeeded)+ sizeof($this->failed)+ sizeof($this->skipped);
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
}
