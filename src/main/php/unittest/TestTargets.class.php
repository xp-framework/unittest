<?php namespace unittest;

use lang\IllegalArgumentException;
use lang\reflect\TargetInvocationException;

class TestTargets extends TestGroup {
  private $instance;
  private $tests= [], $before= [], $after= [];

  static function __static() { }

  /**
   * Creates an instance from a type
   *
   * @param  lang.XPClass $type
   * @param  var[] $arguments
   */
  public function __construct($type, $arguments= []) {
    if (!$type->reflect()->isInstantiable()) {
      throw new IllegalArgumentException('Cannot instantiate '.$type->getName());
    }

    $this->instance= $type->newInstance(...$this->arguments);
    foreach ($type->getMethods() as $method) {
      if ($method->hasAnnotation('test')) {
        $this->tests[]= $method;
      } else if ($method->hasAnnotation('before')) {
        $this->before[]= $method;
      } else if ($method->hasAnnotation('after')) {
        $this->after[]= $method;
      }
    }
  }

  /**
   * Runs actions before this group
   *
   * @return void
   * @throws unittest.PrerequisitesNotMetError
   */
  public function before() {
    foreach ($this->before as $method) {
      try {
        $method->invoke($this->instance, []);
      } catch (TargetInvocationException $e) {
        $cause= $e->getCause();
        throw $cause instanceof PrerequisitesNotMetError
          ? $cause
          : new PrerequisitesNotMetError('Exception in @before method '.$method->getName(), $cause);
        ;
      }
    }
  }

  /**
   * Runs actions after this group
   *
   * @return void
   */
  public function after() {
    foreach ($this->after as $method) {
      try {
        $method->invoke($this->instance, []);
      } catch (TargetInvocationException $ignored) { }
    }
  }

  /** @return lang.XPClass */
  public function type() { return typeof($this->instance); }

  /** @return int */
  public function numTests() { return sizeof($this->tests); }

  /** @return iterable */
  public function tests() {
    $instance= $this->instance;
    foreach ($this->tests as $method) {
      $name= $method->getName();
      yield newinstance(TestCase::class, [$name], [
        $name => function() use($instance, $name) {
          return $instance->{$name}();
        }
      ]);
    }
  }

  /** @return iterable */
  public function targets() {
    foreach ($this->tests as $method) {
      yield new Test($this->instance, $method);
    }
  }
}