<?php namespace unittest;

use lang\Type;
use util\Objects;

/**
 * Default assertion set
 *
 * @test  xp://unittest.tests.AssertTest
 */
abstract class Assert {

  /**
   * Assert that two values are equal
   *
   * @param  var $expected
   * @param  var $actual
   * @param  string $error
   * @return void
   */
  public static function equals($expected, $actual, $error= 'equals') {
    if (!Objects::equal($expected, $actual)) {
      throw new AssertionFailedError(new ComparisonFailedMessage($error, $expected, $actual));
    }
  }

  /**
   * Assert that two values are not equal
   *
   * @param  var expected
   * @param  var actual
   * @param  string error default 'equal'
   * @return void
   */
  public static function notEquals($expected, $actual, $error= '!equals') {
    if (Objects::equal($expected, $actual)) {
      throw new AssertionFailedError(new ComparisonFailedMessage($error, $expected, $actual));
    }
  }

  /**
   * Assert that a value is true
   *
   * @param  var $actual
   * @param  string $error default '==='
   * @return void
   */
  public static function true($actual, $error= '===') {
    if (true !== $actual) {
      throw new AssertionFailedError(new ComparisonFailedMessage($error, true, $actual));
    }
  }
  
  /**
   * Assert that a value is false
   *
   * @param  var $actual
   * @param  string $error default '==='
   * @return void
   */
  public static function false($actual, $error= '===') {
    if (false !== $actual) {
      throw new AssertionFailedError(new ComparisonFailedMessage($error, false, $actual));
    }
  }

  /**
   * Assert that a value's type is null
   *
   * @param  var $actual
   * @param  string $error default '==='
   * @return void
   */
  public static function null($actual, $error= '===') {
    if (null !== $actual) {
      throw new AssertionFailedError(new ComparisonFailedMessage($error, null, $actual));
    }
  }

  /**
   * Assert that a given object is a subclass of a specified class
   *
   * @param  string|lang.Type $type
   * @param  var $actual
   * @param  string $error default 'instanceof'
   * @return void
   */
  public static function instance($type, $actual, $error= 'instanceof') {
    $t= $type instanceof Type ? $type : Type::forName($type);
    if (!$t->isInstance($actual)) {
      throw new AssertionFailedError(new ComparisonFailedMessage($error, $t->getName(), typeof($actual)->getName()));
    }
  }
}