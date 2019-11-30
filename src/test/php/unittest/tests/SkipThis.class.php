<?php namespace unittest\tests;

use lang\IllegalStateException;
use unittest\{PrerequisitesNotMetError, Test, TestAction};

/**
 * This class is used in the TestActionTest 
 */
class SkipThis implements TestAction {

  /**
   * Before test: Update field
   *
   * @param  unittest.Test $t
   */
  public function beforeTest(Test $t) {
    throw new PrerequisitesNotMetError('Skip');
  }

  /**
   * After test: Update field
   *
   * @param  unittest.Test $t
   */
  public function afterTest(Test $t) {
    throw new IllegalStateException('Should never be run!'); 
  }
}