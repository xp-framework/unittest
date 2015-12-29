<?php namespace unittest;

use lang\XPClass;

abstract class TestGroup {
  protected static $base;

  static function __static() {
    self::$base= new XPClass(TestCase::class);
  }
}