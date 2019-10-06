<?php namespace unittest;

use lang\Throwable;

/**
 * Indicates a test failed
 *
 * @see   xp://unittest.TestFailure
 */
class TestError extends TestFailure {

  /**
   * Constructor
   *
   * @param  unittest.Test $test
   * @param  lang.Throwable $reason
   * @param  double $elapsed
   */
  public function __construct(Test $test, Throwable $reason, $elapsed) {
    parent::__construct($test, $elapsed);
    $this->reason= $reason;
  }

  /** @return string */
  protected function formatReason() { return $this->reason->toString(); }
}
