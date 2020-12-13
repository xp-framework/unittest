<?php namespace unittest;

use lang\{Reflect, IllegalArgumentException, IllegalStateException};
use util\NoSuchElementException;

class TestClass extends TestGroup {
  private $reflect, $actions, $arguments;
  private $tests= [];

  static function __static() { }

  /**
   * Creates an instance from a testcase
   *
   * @param  lang.reflection.Type $reflect
   * @param  var[] $args
   * @throws lang.IllegalArgumentException in case given argument is not a testcase class
   * @throws lang.IllegalStateException in case a test method is overridden
   * @throws util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function __construct($reflect, $arguments) {
    if (!$reflect->is(self::$base)) {
      throw new IllegalArgumentException('Given argument is not a TestCase class ('.$reflect->name().')');
    }

    foreach ($reflect->methods() as $name => $method) {
      if ($method->annotations()->provides(Test::class)) {
        if (self::$base->method($name)) {
          throw new IllegalStateException(sprintf(
            'Cannot override %s::%s with test method in %s',
            self::$base->name(),
            $name,
            $method->declaredIn()->name()
          ));
        }
        $this->tests[$name]= $method;
      }
    }

    if (empty($this->tests)) {
      throw new NoSuchElementException('No tests found in '.$reflect->name());
    }

    $this->reflect= $reflect;
    $this->actions= iterator_to_array($this->actionsFor($reflect, TestAction::class));
    $this->arguments= (array)$arguments;
  }

  /** @return lang.reflection.Type */
  public function reflect() { return $this->reflect; }

  /** @return int */
  public function numTests() { return sizeof($this->tests); }

  /** @return iterable */
  public function tests() {
    foreach ($this->tests as $name => $method) {
      yield new TestCaseInstance(
        $this->reflect->newInstance($name, ...$this->arguments),
        $method,
        array_merge($this->actions, iterator_to_array($this->actionsFor($method, TestAction::class)))
      );
    }
  }
}