<?php namespace unittest;

use util\Objects;

/**
 * Indicates a test was skipped
 *
 * @see   xp://unittest.TestPrerequisitesNotMet
 */
class TestSkipped extends TestOutcome {
  public $reason;

  /** @return string */
  public function toString() {
    return parent::toString()." {\n  ".str_replace("\n", "\n  ", Objects::stringOf($this->reason))."\n}";
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
