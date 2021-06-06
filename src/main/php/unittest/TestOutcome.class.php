<?php namespace unittest;

use lang\Value;
use util\Objects;

/** Outcome from a test */
abstract class TestOutcome implements Value {
  public $test, $elapsed;

  /**
   * Constructor
   *
   * @param  unittest.Test $test
   * @param  float $elapsed
   */
  public function __construct(Test $test, $elapsed) {
    $this->test= $test;
    $this->elapsed= $elapsed;
  }

  /** @return var[] */
  public function source() { return $this->test->declaration(); }

  /** @return string */
  public abstract function event();

  /** @return unittest.Test */
  public function test() { return $this->test; }

  /** @return double */
  public function elapsed() { return $this->elapsed; }

  /** @return string */
  public function toString() {
    return sprintf('%s(test= %s, time= %.3f seconds)', nameof($this), $this->test->getName(true), $this->elapsed);
  }

  /** @return string */
  public function hashCode() {
    return 'O'.$this->test->hashCode();
  }

  /**
   * Compares this test outcome to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare(([$this->test, $this->elapsed]), [$value->test, $value->elapsed])
      : 1
    ;
  }
}