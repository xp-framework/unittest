<?php namespace unittest;

/**
 * Indicates a test was successful
 *
 * @see   xp://unittest.TestSuccess
 */
class TestExpectationMet implements TestSuccess {
  public
    $test     = null,
    $elapsed  = 0.0;
    
  /**
   * Constructor
   *
   * @param   unittest.TestCase test
   * @param   float elapsed
   */
  public function __construct(TestCase $test, $elapsed) {
    $this->test= $test;
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
      '%s(test= %s, time= %.3f seconds)',
      nameof($this),
      $this->test->getName(true),
      $this->elapsed
    );
  }

  /** @return string */
  public function hashCode() {
    return $this->test->hashCode();
  }

  /**
   * Compares this test outcome to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? $this->test->compareTo($value->test) : 1;
  }
}
