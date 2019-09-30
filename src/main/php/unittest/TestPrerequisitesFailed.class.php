<?php namespace unittest;

/**
 * Indicates a test failed
 *
 * @see      xp://unittest.TestFailure
 */
class TestPrerequisitesFailed extends TestFailure {
    
  /**
   * Constructor
   *
   * @param  unittest.Test $test
   * @param  unittest.PrerequisitesNotMetError $reason
   * @param  double $elapsed
   */
  public function __construct(Test $test, PrerequisitesFailedError $reason, $elapsed) {
    parent::__construct($test, $elapsed);
    $this->reason= $reason;
  }

  /** @return string */
  protected function formatReason() { return $this->reason->toString(); }
}
