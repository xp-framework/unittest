<?php namespace unittest;

use lang\MethodNotImplementedException;

class TestInstance extends TestGroup {
  private $instance, $target;

  static function __static() { }

  /**
   * Creates an instance from a testcase
   *
   * @param  unittest.TestCase $instance
   * @throws lang.IllegalStateException for overriding test class methods with tests
   * @throws lang.MethodNotImplementedException in case given argument is not a valid testcase
   */
  public function __construct($instance) {
    $class= typeof($instance);
    if (!$class->hasMethod($instance->name)) {
      throw new MethodNotImplementedException('Test method does not exist', $instance->name);
    }

    $method= $class->getMethod($instance->name);
    if (self::$base->hasMethod($instance->name)) {
      throw $this->cannotOverride($method);
    }

    $this->instance= $instance;
    $this->target= new TestCaseInstance($instance, $method, array_merge(
      iterator_to_array($this->actionsFor($class, TestAction::class)),
      iterator_to_array($this->actionsFor($method, TestAction::class))
    ));
  }

  /** @return lang.XPClass */
  public function type() { return typeof($this->instance); }

  /** @return int */
  public function numTests() { return 1; }

  /** @return iterable */
  public function tests() { yield $this->instance; }

  /** @return iterable */
  public function targets() { yield $this->target; }
}