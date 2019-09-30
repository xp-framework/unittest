<?php namespace unittest;

interface TestAction {

  /**
   * This method gets invoked before a test method is invoked, and before
   * the setUp() method is called.
   *
   * @param  unittest.Test $t
   * @return void
   * @throws unittest.PrerequisitesNotMetError
   */
  public function beforeTest(Test $t);

  /**
   * This method gets invoked after the test method is invoked and regard-
   * less of its outcome, after the tearDown() call has run.
   *
   * @param  unittest.Test $t
   * @return void
   */
  public function afterTest(Test $t);
}
