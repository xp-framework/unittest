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

  /**
   * Returns the source of an exception.
   * 
   * @param  lang.Throwable $t
   * @return var[]
   */
  protected function sourceOf($t) {
    $trace= $t->getStackTrace()[0];

    // If invoked by reflection no file and line are present, however class and
    // method are - retrieve their declaration
    if (null === $trace->file && $trace->class) {
      $m= new \ReflectionMethod($trace->class, $trace->method);
      return [$m->getFileName(), $m->getEndLine() - 1];
    }

    return [$trace->file, $trace->line];
  }

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