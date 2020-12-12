<?php namespace unittest;

use lang\reflection\Type;
use lang\{Reflection, Throwable, IllegalArgumentException};
use util\NoSuchElementException;

class TestTargets extends TestGroup {
  private $reflect, $instance, $actions;
  private $tests= [], $before= [], $after= [];

  static function __static() { }

  /**
   * Creates an instance from a type
   *
   * @param  lang.XPClass|lang.reflection.Type $type
   * @param  var[] $arguments
   * @throws lang.IllegalArgumentException in case given argument is not instantiable
   * @throws util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function __construct($type, $arguments= []) {
    $reflect= $type instanceof Type ? $type : Reflection::of($type);
    try {
      $this->instance= $reflect->newInstance(...$arguments);
    } catch (Throwable $e) {
      throw new IllegalArgumentException('Error instantiating '.$reflect->name(), $e);
    }

    $this->actions= iterator_to_array($this->actionsFor($reflect, TestAction::class));
    foreach ($reflect->methods() as $method) {
      $annotations= $method->annotations();
      if ($annotations->provides(Test::class)) {
        $this->tests[]= $method;
      } else if ($annotations->provides(Before::class)) {
        $this->before[]= $method;
      } else if ($annotations->provides(After::class)) {
        $this->after[]= $method;
      }
    }

    if (empty($this->tests)) {
      throw new NoSuchElementException('No tests found in '.$reflect->name());
    }

    $this->reflect= $reflect;
  }

  /** @return iterable */
  protected function beforeGroup() {
    foreach ($this->before as $m) {
      yield $m->name() => $m->invoke($this->instance, []);
    }
  }

  /** @return iterable */
  protected function afterGroup() {
    foreach ($this->after as $m) {
      yield $m->name() => $m->invoke($this->instance, []);
    }
  }

  /** @return lang.reflection.Type */
  public function reflect() { return $this->reflect; }

  /** @return int */
  public function numTests() { return sizeof($this->tests); }

  /** @return iterable */
  public function tests() {
    foreach ($this->tests as $method) {
      yield new TestTarget($this->instance, $method, array_merge(
        $this->actions,
        iterator_to_array($this->actionsFor($method, TestAction::class))
      ));
    }
  }
}