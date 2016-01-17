<?php namespace unittest;

use lang\mirrors\TypeMirror;
use lang\IllegalStateException;

abstract class TestGroup {
  protected static $base;

  static function __static() {
    self::$base= new TypeMirror(TestCase::class);
  }

  /**
   * Verify test method doesn't override a special method from TestCase
   *
   * @param  lang.mirrors.Method $method
   * @return lang.IllegalStateException
   */
  protected function cannotOverride($method) {
    return new IllegalStateException(sprintf(
      'Cannot override %s::%s with test method in %s',
      self::$base->name(),
      $method->name(),
      $method->declaredIn()->name()
    ));
  }

  /** @return int */
  public abstract function numTests();

  /** @return php.Generator */
  public abstract function tests();
}