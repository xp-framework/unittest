<?php namespace unittest;

use lang\XPClass;
use lang\IllegalStateException;

abstract class TestGroup {
  protected static $base;

  static function __static() {
    self::$base= new XPClass(TestCase::class);
  }

  /**
   * Verify test method doesn't override a special method from TestCase
   *
   * @param  lang.reflect.Method $method
   * @return lang.IllegalStateException
   */
  protected function cannotOverride($method) {
    return new IllegalStateException(sprintf(
      'Cannot override %s::%s with test method in %s',
      self::$base->getName(),
      $method->getName(),
      $method->getDeclaringClass()->getName()
    ));
  }

  /** @return lang.XPClass */
  public abstract function type();

  /** @return int */
  public abstract function numTests();

  /** @return php.Generator */
  public abstract function tests();
}