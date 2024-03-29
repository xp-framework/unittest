<?php namespace unittest;

use util\profiling\Timer;

/**
 * Indicates prerequisites have failed
 *
 * @see  xp://unittest.TestPrerequisitesFailed
 */
class PrerequisitesFailedError extends PrerequisitesNotMetError {

  /** @return unittest.TestOutcome */
  public function outcome(Test $test, Timer $timer) {
    return new TestPrerequisitesFailed($test, $this, $timer->elapsedTime());
  }
}