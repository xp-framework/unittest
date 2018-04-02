<?php namespace unittest;

use lang\IllegalArgumentException;
use util\NoSuchElementException;
use util\Objects;

class TestClass extends TestGroup {
  private $class, $arguments;
  private $testMethods= [];

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
        $this->testMethods[]= $name;
      }
    }

    if (empty($this->testMethods)) {
      throw new NoSuchElementException('No tests found in '.$class->getName());
    }

    $this->class= $class;
    $this->arguments= (array)$arguments;
  }

  /** @return lang.XPClass */
  public function type() { return $this->class; }

  /** @return int */
  public function numTests() { return sizeof($this->testMethods); }

  /** @return php.Generator */
  public function tests() {
    $constructor= $this->class->getConstructor();
    foreach ($this->testMethods as $name) {
      yield $constructor->newInstance(array_merge([$name], $this->arguments));
    }
  }
}