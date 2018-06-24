<?php namespace xp\unittest\sources;

use lang\IllegalArgumentException;
use lang\reflect\Modifiers;
use unittest\TestCase;

abstract class ClassesSource {

  /** @return iterable */
  protected abstract function classes();

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   * @throws lang.IllegalArgumentException if no tests are found
   */
  public function provideTo($suite, $arguments) {
    $empty= true;

    foreach ($this->classes() as $class) {
      if ($class->isSubclassOf(TestCase::class) && !Modifiers::isAbstract($class->getModifiers())) {
        $suite->addTestClass($class, $arguments);
        $empty= false;
      }
    }

    if ($empty) {
      throw new IllegalArgumentException('Cannot find any test cases in '.$this->toString());
    }
  }
}
