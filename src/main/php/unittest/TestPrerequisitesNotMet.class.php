<?php namespace unittest;

/**
 * Indicates a test was skipped
 *
 * @see      xp://unittest.TestSkipped
 */
class TestPrerequisitesNotMet extends TestSkipped {
    
  /**
   * Constructor
   *
   * @param  unittest.Test $test
   * @param  unittest.PrerequisitesNotMetError $reason
   * @param  double $elapsed
   */
  public function __construct(Test $test, PrerequisitesNotMetError $reason, $elapsed) {
    parent::__construct($test, $elapsed);
    $this->reason= $reason;
  }
}
