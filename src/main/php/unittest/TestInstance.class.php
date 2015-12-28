<?php namespace unittest;

use lang\ElementNotFoundException;
use lang\MethodNotImplementedException;

class TestInstance extends TestGroup {
  private $target;

  static function __static() { }

  /**
   * Creates a group based on a single testcase instance
   *
   * @param  unittest.TestCase $instance
   * @throws unittest.MethodNotImplementedException
   */
  public function __construct($instance) {
    $class= typeof($instance);

    try {
      $this->setupMethod($class->getMethod($instance->name));
    } catch (ElementNotFoundException $e) {
      throw new MethodNotImplementedException('Test method does not exist', $instance->name);
    }

    $before= $after= [];
    $this->target= new TestTarget($instance, $before, $after);
    foreach ($class->getMethods() as $method) {
      $this->withMethod($method, $before, $after);
    }

    $this->setupClass($class);
  }

  /** @return bool */
  public function hasTests() { return true; }

  /** @return int */
  public function numTests() { return 1; }

  /** @return unittest.TestTarget[] */
  public function targets() { return [$this->target]; }

}