<?php namespace unittest;

use lang\ElementNotFoundException;
use lang\MethodNotImplementedException;

class TestInstance extends TestGroup {
  private $target;

  static function __static() { }

  public function __construct(TestCase $instance) {
    $class= typeof($instance);

    try {
      $this->verifyMethod($class->getMethod($instance->name));
    } catch (ElementNotFoundException $e) {
      throw new MethodNotImplementedException('Test method does not exist', $instance->name);
    }

    $before= $after= [];
    $this->target= new TestTarget($instance, $before, $after);
    foreach ($class->getMethods() as $method) {
      $this->withMethod($method, $before, $after);
    }

    $this->verifyClass($class);
  }

  /** @return bool */
  public function hasTests() { return true; }

  /** @return int */
  public function numTests() { return 1; }

  /** @return unittest.TestTarget[] */
  public function targets() { return [$this->target]; }

}