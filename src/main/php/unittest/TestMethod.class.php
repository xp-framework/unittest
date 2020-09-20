<?php namespace unittest;

use lang\IllegalArgumentException;
use lang\reflect\TargetInvocationException;
use util\NoSuchElementException;

class TestMethod extends TestGroup {
  private $instance, $actions, $method;
  private $before= [], $after= [];

  static function __static() { }

  /**
   * Creates an instance from a type
   *
   * @param  lang.XPClass $type
   * @param  string $method
   * @param  var[] $arguments
   * @throws lang.IllegalArgumentException in case given argument is not instantiable
   * @throws util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function __construct($type, $method, $arguments= []) {
    $reflect= $type->reflect();
    if (!$reflect->isInstantiable()) {
      throw new IllegalArgumentException('Cannot instantiate '.$type->getName());
    }

    if (!$type->hasMethod($method)) {
      throw new NoSuchElementException('Given method '.$method.' does no exist');
    }
    $this->method= $type->getMethod($method);

    $this->instance= $reflect->hasMethod('__construct') ? $type->newInstance(...$arguments) : $type->newInstance();
    $this->actions= iterator_to_array($this->actionsFor($type, TestAction::class));
    foreach ($type->getMethods() as $method) {
      if ($method->hasAnnotation('before')) {
        $this->before[]= $method;
      } else if ($method->hasAnnotation('after')) {
        $this->after[]= $method;
      }
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
  public function numTests() { return 1; }

  /** @return iterable */
  public function tests() {
    yield new TestTarget($this->instance, $this->method, array_merge(
      $this->actions,
      iterator_to_array($this->actionsFor($this->method, TestAction::class))
    ));
  }
}