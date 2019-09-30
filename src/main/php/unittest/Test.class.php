<?php namespace unittest;

use lang\Value;

abstract class Test implements Value {

  /**
   * Get this test's name
   *
   * @param  bool $compound whether to use compound format
   * @return string
   */
  public abstract function getName($compound= false);

  /**
   * Creates a hashcode of this testcase
   *
   * @return string
   */
  public abstract function hashCode();

  /**
   * Creates a string representation of this testcase
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'<'.$this->getName(true).'>';
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? strcmp($this->getName(true), $value->getName(true)) : 1;
  }
}