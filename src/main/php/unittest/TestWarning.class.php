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
   * @param  unittest.Test $test
   * @param  string[] $warnings
   * @param  float $elapsed
   */
  public function __construct(Test $test, array $warnings, $elapsed) {
    parent::__construct($test, $elapsed);
    $this->reason= new Warnings($warnings);
  }

  /** @return var[] */
  public function source() { return $this->reason->first(); }

  /** @return string */
  public function event() { return 'testWarning'; }

  /** @return string */
  protected function formatReason() { return $this->reason->compoundMessage(); }

}