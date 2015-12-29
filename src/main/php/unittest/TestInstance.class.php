<?php namespace unittest;

use lang\IllegalStateException;
use lang\MethodNotImplementedException;

class TestInstance extends TestGroup {
  private $instance;

  static function __static() { }

  /**
   * Creates an instance from a testcase
   *
   * @param  unittest.TestCase $instance
   * @throws lang.IllegalStateException for overriding test class methods with tests
   * @throws lang.MethodNotImplementedException in case given argument is not a valid testcase
   */
  public function __construct($instance) {
    if (!$instance->getClass()->hasMethod($instance->name)) {
      throw new MethodNotImplementedException('Test method does not exist', $instance->name);
    }

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

  /** @return int */
  public function numTests() { return 1; }

  /** @return php.Generator */
  public function tests() {
    yield $this->instance;
  }
}