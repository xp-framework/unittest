<?php namespace unittest;

use lang\IllegalStateException;
use lang\IllegalArgumentException;
use util\NoSuchElementException;

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
      throw new IllegalArgumentException('Given argument is not a TestCase class ('.\xp::stringOf($class).')');
    }

    foreach ($class->getMethods() as $method) {
      if ($method->hasAnnotation('test')) {
        if (self::$base->hasMethod($method->getName())) {
          throw new IllegalStateException(sprintf(
            'Cannot override %s::%s with test method in %s',
            self::$base->getName(),
            $method->getName(),
            $method->getDeclaringClass()->getName()
          ));
        }
        $this->testMethods[]= $method;
      }
    }

    if (empty($this->testMethods)) {
      throw new NoSuchElementException('No tests found in '.$class->getName());
    }

    $this->class= $class;
    $this->arguments= $arguments;
  }

  /** @return int */
  public function numTests() { return sizeof($this->testMethods); }

  /** @return php.Generator */
  public function tests() {
    $constructor= $this->class->getConstructor();
    foreach ($this->testMethods as $method) {
      yield $constructor->newInstance(array_merge(
        [$method->getName()],
        (array)$this->arguments
      ));
    }
  }
}