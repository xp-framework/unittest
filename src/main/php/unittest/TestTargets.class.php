<?php namespace unittest;

use lang\IllegalArgumentException;
use lang\reflect\TargetInvocationException;
use util\NoSuchElementException;

class TestTargets extends TestGroup {
  private $instance, $actions;
  private $tests= [], $before= [], $after= [];

  static function __static() { }

  /**
   * Creates an instance from a type
   *
   * @param  lang.XPClass $type
   * @param  var[] $arguments
   * @throws lang.IllegalArgumentException in case given argument is not instantiable
   * @throws util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function __construct($type, $arguments= []) {
    if (!$type->reflect()->isInstantiable()) {
      throw new IllegalArgumentException('Cannot instantiate '.$type->getName());
    }

    $this->instance= $type->newInstance(...$this->arguments);
    $this->actions= iterator_to_array($this->actionsFor($type, TestAction::class));
    foreach ($type->getMethods() as $method) {
      if ($method->hasAnnotation('test')) {
        $this->tests[]= $method;
      } else if ($method->hasAnnotation('before')) {
        $this->before[]= $method;
      } else if ($method->hasAnnotation('after')) {
        $this->after[]= $method;
      }
    }

    if (empty($this->tests)) {
      throw new NoSuchElementException('No tests found in '.$class->getName());
    }
  }

  /** @return iterable */
  protected function beforeGroup() {
    foreach ($this->before as $m) {
      yield $m->getName() => $m->invoke($this->instance, []);
    }
  }

  /** @return iterable */
  protected function afterGroup() {
    foreach ($this->after as $m) {
      yield $m->getName() => $m->invoke($this->instance, []);
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
      yield new TestTarget($this->instance, $method, array_merge(
        $this->actions,
        iterator_to_array($this->actionsFor($method, TestAction::class))
      ));
    }
  }
}