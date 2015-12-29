<?php namespace unittest;

use lang\IllegalStateException;
use lang\XPClass;
use util\NoSuchElementException;

class TestClass {
  private $class, $arguments;
  private static $base;

  static function __static() {
    self::$base= new XPClass(TestCase::class);
  }

  /**
   * Creates an instance from a testcase
   *
   * @param  lang.XPClass $class
   * @param  var[] $args
   * @throws lang.IllegalStateException
   * @throws util.NoSuchElementException
   */
  public function __construct($class, $arguments) {
    $empty= true;
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
        $empty= false;
      }
    }

    if ($empty) {
      throw new NoSuchElementException('No tests found in '.$class->getName());
    }

    $this->class= $class;
    $this->arguments= $arguments;
  }

  /** @return php.Generator */
  public function tests() {
    foreach ($this->class->getMethods() as $method) {
      if ($method->hasAnnotation('test')) {
        yield $this->class->getConstructor()->newInstance(array_merge(
          [$method->getName()],
          (array)$this->arguments
        ));
      }
    }
  }
}