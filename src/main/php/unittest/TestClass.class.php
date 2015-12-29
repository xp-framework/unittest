<?php namespace unittest;

use lang\IllegalArgumentException;

class TestClass extends TestGroup {
  private $targets= [];

  static function __static() { }

  /**
   * Creates a group based on a class with various test methods
   *
   * @param  lang.XPClass $instance
   * @param  var[] $args
   * @throws unittest.IllegalArgumentException
   */
  public function __construct($class, $arguments) {
    if (!$class->isSubclassOf(self::$base)) {
      throw new IllegalArgumentException('Given argument is not a TestCase class ('.\xp::stringOf($class).')');
    }

    $before= $after= [];
    foreach ($class->getMethods() as $method) {
      if ($method->hasAnnotation('test')) {
        $instance= $class->getConstructor()->newInstance(array_merge((array)$method->getName(), $arguments));
        $this->targets[]= new TestTarget($instance, $method, $before, $after);
      } else {
        $this->withMethod($method, $before, $after);
      }
    }

    $this->setupClass($class);
  }

  /** @return bool */
  public function hasTests() { return !empty($this->targets); }

  /** @return int */
  public function numTests() { return sizeof($this->targets); }

  /** @return unittest.TestTarget[] */
  public function targets() { return $this->targets; }

}