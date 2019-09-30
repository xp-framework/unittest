<?php namespace unittest\tests;

/**
 * Tests @beforeClass and @afterClass methods using runTest()
 *
 * @see   xp://unittest.TestSuite
 */
class BeforeAndAfterClassWithRunTestTest extends BeforeAndAfterClassTest {

  /**
   * Runs a test and returns the outcome
   *
   * @param   unittest.Test $test
   * @return  unittest.TestOutcome
   */
  protected function runTest($test) {
    return $this->suite->runTest($test);
  }
}
