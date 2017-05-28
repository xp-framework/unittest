<?php namespace unittest;

use util\Objects;

/**
 * Indicates a test failed
 *
 * @see   xp://unittest.TestFailure
 */
class TestAssertionFailed implements TestFailure {
  public
    $reason   = null,
    $test     = null,
    $elapsed  = 0.0;
    
  /**
   * Constructor
   *
   * @param   unittest.TestCase test
   * @param   unittest.AssertionFailedError reason
   * @param   float elapsed
   */
  public function __construct(TestCase $test, AssertionFailedError $reason, $elapsed) {
    $this->test= $test;
    $this->reason= $reason;
    $this->elapsed= $elapsed;
  }

  /**
   * Returns elapsed time
   *
   * @return  float
   */
  public function elapsed() {
    return $this->elapsed;
  }

  /**
   * Return a string representation of this class
   *
   * @return  string
   */
  public function toString() {
    return sprintf(
      "%s(test= %s, time= %.3f seconds) {\n  %s\n }",
      nameof($this),
      $this->test->getName(true),
      $this->elapsed,
      \xp::stringOf($this->reason, '  ')
    );
  }

  /** @return string */
  public function hashCode() {
    return Objects::hashOf([$this->test, $this->reason]);
  }

  /**
   * Compares this test outcome to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare([$this->test, $this->reason], [$value->test, $value->reason]) : 1;
  }
}
