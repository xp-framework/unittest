<?php namespace unittest;

use lang\{Reflection, IllegalStateException, MethodNotImplementedException};

class TestInstance extends TestGroup {
  private $target, $reflect;

  static function __static() { }

  /**
   * Creates an instance from a testcase
   *
   * @param  unittest.TestCase $instance
   * @throws lang.IllegalStateException for overriding test class methods with tests
   * @throws lang.MethodNotImplementedException in case given argument is not a valid testcase
   */
  public function __construct($instance) {
    $this->reflect= Reflection::of($instance);
    if (null === ($method= $this->reflect->method($instance->name))) {
      throw new MethodNotImplementedException('Test method does not exist', $instance->name);
    }

    if (self::$base->method($instance->name)) {
      throw new IllegalStateException(sprintf(
        'Cannot override %s::%s with test method in %s',
        self::$base->name(),
        $instance->name,
        $method->declaredIn()->name()
      ));
    }

    $this->target= new TestCaseInstance($instance, $method, array_merge(
      iterator_to_array($this->actionsFor($this->reflect, TestAction::class)),
      iterator_to_array($this->actionsFor($method, TestAction::class))
    ));
  }

  /** @return lang.reflection.Type */
  public function reflect() { return $this->reflect; }

  /** @return int */
  public function numTests() { return 1; }

  /** @return iterable */
  public function tests() { yield $this->target; }
}