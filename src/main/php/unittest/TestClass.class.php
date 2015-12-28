<?php namespace unittest;

use lang\IllegalArgumentException;

class TestClass extends TestGroup {
  private $targets= [];

  static function __static() { }

  public function __construct($class, $arguments) {
    $before= $after= [];
    foreach ($class->getMethods() as $method) {
      if ($method->hasAnnotation('test')) {
        $this->testMethod($method);
        $instance= $class->getConstructor()->newInstance(array_merge((array)$method->getName(), $arguments));
        $this->targets[]= new TestTarget($instance, $before, $after);
      } else {
        $this->withMethod($method, $before, $after);
      }
    }

    $this->testClass($class);
  }

  /** @return bool */
  public function hasTests() { return !empty($this->targets); }

  /** @return int */
  public function numTests() { return sizeof($this->targets); }

  /** @return unittest.TestTarget[] */
  public function targets() { return $this->targets; }

}