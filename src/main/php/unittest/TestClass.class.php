<?php namespace unittest;

use lang\IllegalArgumentException;
use util\NoSuchElementException;
use util\Objects;

class TestClass extends TestGroup {
  private $class, $actions, $arguments;
  private $tests= [];

  static function __static() { }

  /**
   * Creates an instance from a testcase
   *
   * @param  lang.XPClass $class
   * @param  var[] $args
   * @throws lang.IllegalArgumentException in case given argument is not a testcase class
   * @throws lang.IllegalStateException in case a test method is overridden
   * @throws util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function __construct($class, $arguments) {
    if (!$class->isSubclassOf(self::$base)) {
      throw new IllegalArgumentException('Given argument is not a TestCase class ('.Objects::stringOf($class).')');
    }

    foreach ($class->getMethods() as $method) {
      if ($method->hasAnnotation('test')) {
        $name= $method->getName();
        if (self::$base->hasMethod($name)) {
          throw $this->cannotOverride($method);
        }
        $this->tests[$name]= $method;
      }
    }

    if (empty($this->tests)) {
      throw new NoSuchElementException('No tests found in '.$class->getName());
    }

    $this->class= $class;
    $this->actions= iterator_to_array($this->actionsFor($class, TestAction::class));
    $this->arguments= (array)$arguments;
  }

  /** @return lang.XPClass */
  public function type() { return $this->class; }

  /** @return int */
  public function numTests() { return sizeof($this->tests); }

  /** @return iterable */
  public function tests() {
    $constructor= $this->class->getConstructor();
    foreach ($this->tests as $name => $_) {
      yield $constructor->newInstance(array_merge([$name], $this->arguments));
    }
  }

  /** @return iterable */
  public function targets() {
    $constructor= $this->class->getConstructor();
    foreach ($this->tests as $name => $method) {
      yield new TestClassInstance(
        $constructor->newInstance(array_merge([$name], $this->arguments)),
        $method,
        array_merge($this->actions, iterator_to_array($this->actionsFor($method, TestAction::class)))
      );
    }
  }
}