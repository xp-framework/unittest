<?php namespace unittest;

/**
 * Indicates a test was successful
 *
 * @see   xp://unittest.TestExpectationMet
 */
class TestSuccess extends TestOutcome {

  /** @return string */
  public function event() { return 'testSucceeded'; }

}