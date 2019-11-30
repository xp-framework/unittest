<?php namespace unittest;

use lang\{Generic, Value};
use util\Objects;

/**
 * The message for an assertion failure
 *
 * @see  xp://unittest.AssertionFailedError
 * @test xp://net.xp_framework.unittest.tests.AssertionMessagesTest
 */
class ComparisonFailedMessage implements AssertionFailedMessage {
  const CONTEXT_LENGTH = 20;

  protected $comparison;
  protected $expect;
  protected $actual;

  /**
   * Constructor
   *
   * @param   string message
   * @param   var expect
   * @param   var actual
   */
  public function __construct($comparison, $expect, $actual) {
    $this->comparison= $comparison;
    $this->expect= $expect;
    $this->actual= $actual;
  }

  /**
   * Creates a string representation of a given value.
   *
   * @param  var $value
   * @param  string|lang.Type $type NULL if type name should be not included.
   * @return string
   */
  protected function stringOf($value, $type) {
    if (null === $value) {
      return 'null';
    } else if ($value instanceof Value || $value instanceof Generic) {
      return $value->toString();
    } else if ($type) {
      return $type.':'.Objects::stringOf($value);
    } else {
      return Objects::stringOf($value);
    }
  }

  /**
   * Compacts a string
   *
   * @param  string s
   * @param  int p common postfix offset
   * @param  int s common suffix offset
   * @param  int l length of the given string
   */
  protected function compact($str, $p, $s, $l) {
    $result= substr($str, $p, $s- $p);
    if ($p > 0) {
      $result= ($p < self::CONTEXT_LENGTH ? substr($str, 0, $p) : '...').$result; 
    }
    if ($s < $l) {
      $result= $result.($l- $s < self::CONTEXT_LENGTH ? substr($str, $s) : '...');
    }
    return '"'.$result.'"';
  }

  /**
   * Return formatted message - "expected ... but was .... using: ..."
   *
   * @return  string
   */
  public function format() {
    if (is_string($this->expect) && is_string($this->actual)) {
      $la= strlen($this->actual);
      $le= strlen($this->expect);
      for ($i= 0, $l= min($le, $la); $i < $l; $i++) {                     // Search from beginning
        if ($this->expect[$i] !== $this->actual[$i]) break;
      }
      for ($j= $le- 1, $k= $la- 1; $k >= $i && $j >= $i; $k--, $j--) {    // Search from end
        if ($this->expect[$j] !== $this->actual[$k]) break;
      }
      $expect= $this->compact($this->expect, $i, $j+ 1, $le);
      $actual= $this->compact($this->actual, $i, $k+ 1, $la);
    } else {
      $te= typeof($this->expect);
      $ta= typeof($this->actual);
      $exclude= $te->equals($ta);
      $expect= $this->stringOf($this->expect, $exclude ? null : $te);
      $actual= $this->stringOf($this->actual, $exclude ? null : $ta);
    }

    return sprintf(
      "expected [%s] but was [%s] using: '%s'",
      $expect,
      $actual,
      $this->comparison
    );
  }
}