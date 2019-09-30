<?php namespace unittest;

/**
 * Indicates a test was ignored
 *
 * @see   xp://unittest.TestSkipped
 */
class TestNotRun extends TestSkipped {

  /**
   * Constructor
   *
   * @param  unittest.TestCase $test
   * @param  string $reason
   * @param  double $elapsed
   */
  public function __construct(TestCase $test, $reason) {
    parent::__construct($test, 0.0);
    $this->reason= $reason;
  }
}