<?php namespace unittest\tests;

use lang\IllegalStateException;
use unittest\PrerequisitesNotMetError;
use unittest\Test;
use unittest\TestAction;

/**
 * This class is used in the TestActionTest 
 */
class SkipThisTest implements TestAction {

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
