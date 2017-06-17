<?php namespace unittest;

use util\profiling\Timer;

/**
 * Indicates a test run was aborted
 */
abstract class TestAborted extends \lang\XPException {

  /**
   * Returns the type which is passed to the listeners
   *
   * @see    xp://unittest.TestSuite#notifyListeners
   * @return string
   */ 
  public abstract function type();   

  /**
   * Returns the outcome class
   *
   * @param  unittest.TestCase $test
   * @param  util.profiling.Timer $timer
   * @return unittest.TestOutcome
   */ 
  public abstract function outcome(TestCase $test, Timer $timer);

}