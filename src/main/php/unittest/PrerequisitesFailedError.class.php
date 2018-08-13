<?php namespace unittest;

/**
 * Indicates prerequisites have failed
 *
 * @see  xp://unittest.TestPrerequisitesNotMet
 */
class PrerequisitesFailedError extends PrerequisitesNotMetError {

  /** @return string */
  public function type() { return 'testFailed'; }
}
