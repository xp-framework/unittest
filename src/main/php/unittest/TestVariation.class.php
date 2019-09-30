<?php namespace unittest;

use util\Objects;

/**
 * Test case variation
 *
 * @see   xp://unittest.TestCase
 */
class TestVariation extends Test {
  private $base, $variation;

  /**
   * Constructor
   *
   * @param  unittest.Test $base
   * @param  var[] $args
   */
  public function __construct($base, $args) {
    $this->base= $base;
    $v= '';
    foreach ((array)$args as $arg) {
      $v.= ', '.Objects::stringOf($arg);
    }
    $this->variation= substr($v, 2);
  }

  /**
   * Get this test cases' name
   *
   * @param   bool compound whether to use compound format
   * @return  string
   */
  public function getName($compound= false) {
    return $this->base->getName($compound).'('.$this->variation.')';
  }

  public function hashCode() {
    return md5($this->base->hashCode().$this->variation);
  }
}
