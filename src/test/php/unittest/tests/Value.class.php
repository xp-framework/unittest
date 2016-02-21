<?php namespace unittest\tests;

use util\Objects;

class Value implements \lang\Value {
  private $backing;

  /** @param var $backing */
  public function __construct($backing) { $this->backing= $backing; }

  /** @return string */
  public function hashCode() { return 'V'.Objects::hashOf($this->backing); }

  /** @return string */
  public function toString() { return nameof($this).'('.Objects::stringOf($this->backing).')'; }

  /**
   * Compare
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->backing, $value->backing) : 1;
  }
}