<?php namespace unittest;

use util\profiling\Timer;

/**
 * Indicates an `@ignore` annotation was present
 */
class IgnoredBecause extends TestAborted {
    
  /** @return unittest.TestOutcome */
  public function outcome(Test $test, Timer $timer) {
    return new TestNotRun($test, $this, $timer->elapsedTime());
  }

  /**
   * Return compound message of this exception.
   *
   * @return  string
   */
  public function compoundMessage() {
    return nameof($this).'{ '.$this->message.' }';
  }
}