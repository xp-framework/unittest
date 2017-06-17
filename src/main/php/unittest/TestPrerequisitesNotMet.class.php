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
   * @param  unittest.TestCase $test
   * @param  unittest.PrerequisitesNotMetError $reason
   * @param  double $elapsed
   */
  public function __construct(TestCase $test, PrerequisitesNotMetError $reason, $elapsed) {
    parent::__construct($test, $elapsed);
    $this->reason= $reason;
  }
}
