<?php namespace unittest;

use lang\XPClass;

abstract class TestGroup {
  protected static $base;

  static function __static() {
    self::$base= new XPClass(TestCase::class);
  }

  /** @return int */
  public abstract function numTests();

  /** @return php.Generator */
  public abstract function tests();
}