<?php namespace unittest\tests;

use unittest\TestCase;
use unittest\PrerequisitesNotMetError;
use lang\IllegalStateException;

/**
 * This class is used in the TestActionTest 
 */
class SkipThisTest implements \unittest\TestAction {

  /**
   * Before test: Update field
   *
   * @param  unittest.TestCase $t
   */
  public function beforeTest(TestCase $t) {
    throw new PrerequisitesNotMetError('Skip');
  }

  /**
   * After test: Update field
   *
   * @param  unittest.TestCase $t
   */
  public function afterTest(TestCase $t) {
    throw new IllegalStateException('Should never be run!'); 
  }
}
