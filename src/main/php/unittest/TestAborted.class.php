<?php namespace unittest;

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
   * @return string
   */ 
  public abstract function outcome();   

}