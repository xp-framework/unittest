<?php namespace unittest;

use lang\{Reflection, IllegalArgumentException};
use util\NoSuchElementException;

class TestMethod extends TestGroup {
  private $reflect, $target;
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
    $this->reflect= Reflection::of($type);
    if (null === ($test= $this->reflect->method($method))) {
      throw new NoSuchElementException('Given method '.$method.' does no exist');
    }

    foreach ($this->reflect->methods() as $method) {
      $annotations= $method->annotations();
      if ($annotations->provides(Before::class)) {
        $this->before[]= $method;
      } else if ($annotations->provides(After::class)) {
        $this->after[]= $method;
      }
    }

    $this->target= new TestTarget($this->reflect->newInstance(...$arguments), $test, array_merge(
      iterator_to_array($this->actionsFor($this->reflect, TestAction::class)),
      iterator_to_array($this->actionsFor($test, TestAction::class))
    ));
  }

  /** @return iterable */
  protected function beforeGroup() {
    foreach ($this->before as $m) {
      yield $m->name() => $m->invoke($this->instance);
    }
  }

  /** @return iterable */
  protected function afterGroup() {
    foreach ($this->after as $m) {
      yield $m->name() => $m->invoke($this->instance);
    }
  }

  /** @return lang.reflection.Type */
  public function reflect() { return $this->reflect; }

  /** @return int */
  public function numTests() { return 1; }

  /** @return iterable */
  public function tests() { yield $this->target; }
}