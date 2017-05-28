<?php namespace unittest;

/**
 * Indicates a test was ignored
 *
 * @see   xp://unittest.TestSkipped
 */
class TestNotRun implements TestSkipped {
  public
    $reason   = '',
    $test     = null;
    
  /**
   * Constructor
   *
   * @param   unittest.TestCase test
   * @param   string reason
   */
  public function __construct(TestCase $test, $reason) {
    $this->test= $test;
    $this->reason= $reason;
  }

  /**
   * Returns elapsed time
   *
   * @return  float
   */
  public function elapsed() {
    return 0.0;
  }

  /**
   * Return a string representation of this class
   *
   * @return  string
   */
  public function toString() {
    return sprintf(
      "%s(test= %s, time= 0.000 seconds) {\n  %s\n }",
      nameof($this),
      $this->test->getName(true),
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
