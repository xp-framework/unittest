<?php namespace unittest;

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
    $class= $instance->getClass();
    if (!$class->hasMethod($instance->name)) {
      throw new MethodNotImplementedException('Test method does not exist', $instance->name);
    }

    if (self::$base->hasMethod($instance->name)) {
      throw $this->cannotOverride($class->getMethod($instance->name));
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