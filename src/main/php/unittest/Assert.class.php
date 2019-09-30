<?php namespace unittest;

use util\Objects;

class Assert {

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
}