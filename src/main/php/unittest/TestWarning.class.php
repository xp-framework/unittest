<?php namespace unittest;

/**
 * Indicates a test failed
 *
 * @see      xp://unittest.TestFailure
 */
class TestWarning extends TestFailure {
    
  /**
   * Constructor
   *
   * @param  unittest.TestCase $test
   * @param  string[] $warnings
   * @param  double $elapsed
   */
  public function __construct(TestCase $test, array $warnings, $elapsed) {
    parent::__construct($test, $elapsed);
    $this->reason= new Warnings($warnings);
  }

  /** @return string */
  protected function formatReason() { return $this->reason->compoundMessage(); }

}