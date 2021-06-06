<?php namespace unittest;

use lang\XPException;
use util\profiling\Timer;

/** Indicates a test run was aborted */
abstract class TestAborted extends XPException {

  /**
   * Returns the outcome class
   *
   * @param  unittest.Test $test
   * @param  util.profiling.Timer $timer
   * @return unittest.TestOutcome
   */ 
  public abstract function outcome(Test $test, Timer $timer);

}