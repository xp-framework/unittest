<?php namespace xp\unittest\sources;

abstract class AbstractSource {

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   */
  public abstract function provideTo($suite, $arguments);
}