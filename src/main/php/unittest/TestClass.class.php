<?php namespace unittest;

use lang\IllegalArgumentException;
use lang\mirrors\TypeMirror;
use util\NoSuchElementException;
use util\Filter;

class TestClass extends TestGroup {
  private static $TESTS;
  private $create, $arguments;
  private $testMethods= [];

  static function __static() {
    self::$TESTS= newinstance(Filter::class, [], '{
      public function accept($m) { return $m->annotations()->provides("test"); }
    }');
  }

  /**
   * Creates an instance from a testcase
   *
   * @param  lang.XPClass $class
   * @param  var[] $args
   * @throws lang.IllegalArgumentException in case given argument is not a testcase class
   * @throws lang.IllegalStateException in case a test method is overridden
   * @throws util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function __construct($class, $arguments) {
    $mirror= new TypeMirror($class);
    if (!$mirror->isSubtypeOf(self::$base)) {
      throw new IllegalArgumentException('Given argument is not a TestCase class ('.\xp::stringOf($class).')');
    }

    $base= self::$base->methods();
    foreach ($mirror->methods()->all(self::$TESTS) as $method) {
      $name= $method->name();
      if ($base->provides($name)) {
        throw $this->cannotOverride($method);
      }
      $this->testMethods[]= $name;
    }

    if (empty($this->testMethods)) {
      throw new NoSuchElementException('No tests found in '.$mirror->name());
    }

    $this->create= $mirror->reflect;
    $this->arguments= (array)$arguments;
  }

  /** @return int */
  public function numTests() { return sizeof($this->testMethods); }

  /** @return php.Generator */
  public function tests() {
    foreach ($this->testMethods as $name) {
      yield $this->create->newInstance(array_merge([$name], $this->arguments));
    }
  }
}