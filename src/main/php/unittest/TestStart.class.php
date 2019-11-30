<?php namespace unittest;

use lang\Value;
use util\Objects;

/** Test start */
class TestStart implements Value {
  private $test;

  /**
   * Constructor
   *
   * @param  unittest.Test $test
   */
  public function __construct(Test $test) {
    $this->test= $test;
  }

  /** @return unittest.TestO */
  public function test() { return $this->test; }

  /** @return string */
  public function toString() {
    return sprintf('%s(test= %s)', nameof($this), $this->test->getName(true));
  }

  /** @return string */
  public function hashCode() {
    return '+'.$this->test->hashCode();
  }

  /**
   * Compares this test outcome to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->test, $value->test) : 1;
  }
}