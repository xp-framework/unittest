<?php namespace unittest;

use util\Objects;

/**
 * Test case variation
 *
 * @see   xp://unittest.TestCase
 */
class TestVariation extends Test {
  private $base, $args;
  private $variation= null;

  /**
   * Constructor
   *
   * @param  unittest.Test $base
   * @param  var[] $args
   */
  public function __construct($base, $args) {
    $this->base= $base;
    $this->args= $args;
  }

  /** @return string */
  private function variation() {
    if (null === $this->variation) {
      $v= '';
      foreach ($this->args as $arg) {
        $v.= ', '.Objects::stringOf($arg);
      }
      $this->variation= substr($v, 2);
    }
    return $this->variation;
  }

  /**
   * Runs this test target
   *
   * @param  var[] $args
   * @return void
   * @throws lang.Throwable
   */
  public function run($args) {
    $this->base->run($this->args);
  }

  /** @return string */
  public function container() { return $this->base->container(); }

  /** @return string */
  public function name() { return $this->base->name().'('.$this->variation().')'; }

  /**
   * Get this test cases' name
   *
   * @param   bool compound whether to use compound format
   * @return  string
   */
  public function getName($compound= false) {
    return $this->base->getName($compound).'('.$this->variation().')';
  }

  /** @return string */
  public function hashCode() {
    return md5($this->base->hashCode().$this->variation());
  }
}