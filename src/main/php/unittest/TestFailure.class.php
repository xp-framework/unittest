<?php namespace unittest;

use util\Objects;

/**
 * Indicates a test failed
 *
 * @see   xp://unittest.TestAssertionFailed
 * @see   xp://unittest.TestError
 */
abstract class TestFailure extends TestOutcome {
  public $reason;

  /** @return string */
  protected abstract function formatReason();

  /** @return string */
  public function toString() {
    return parent::toString()." {\n  ".str_replace("\n", "\n  ", $this->formatReason())."\n}";
  }

  /** @return string */
  public function hashCode() {
    return Objects::hashOf([$this->test, $this->elapsed, $this->reason]);
  }

  /**
   * Compares this test outcome to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare([$this->test, $this->elapsed, $this->reason], [$value->test, $this->elapsed, $value->reason])
      : 1
    ;
  }
}