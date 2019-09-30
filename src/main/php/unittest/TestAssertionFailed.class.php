<?php namespace unittest;

/**
 * Indicates a test failed
 *
 * @see   xp://unittest.TestFailure
 */
class TestAssertionFailed extends TestFailure {

  /**
   * Constructor
   *
   * @param  unittest.TestCase $test
   * @param  unittest.AssertionFailedError|unittest.AssertionFailedMessage|string $reason
   * @param  double $elapsed
   */
  public function __construct(TestCase $test, $reason, $elapsed) {
    parent::__construct($test, $elapsed);
    $this->reason= $reason instanceof AssertionFailedError ? $reason : new AssertionFailedError($reason);
  }

  /** @return string */
  protected function formatReason() { return $this->reason->toString(); }

}