<?php namespace unittest;

use lang\IllegalStateException;

class TestInstance extends TestGroup {
  private $instance;

  static function __static() { }

  /**
   * Creates an instance from a testcase
   *
   * @param  unittest.TestCase $instance
   * @throws lang.IllegalStateException
   */
  public function __construct($instance) {
    if (self::$base->hasMethod($instance->name)) {
      throw new IllegalStateException(sprintf(
        'Cannot override %s::%s with test method in %s',
        self::$base->getName(),
        $instance->name,
        nameof($instance)
      ));
    }

    $this->instance= $instance;
  }

  /** @return php.Generator */
  public function tests() {
    yield $this->instance;
  }
}