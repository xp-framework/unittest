<?php namespace xp\unittest\sources;

use lang\IllegalArgumentException;
use lang\reflect\Modifiers;
use lang\reflect\Package;
use unittest\TestCase;

/**
 * Source that load tests from a package
 *
 * @test  xp://unittest.tests.PackageSourceTest
 */
class PackageSource extends AbstractSource {
  private $package, $recursive;
  
  /**
   * Constructor
   *
   * @param  lang.reflect.Package $package
   * @param  bool $recursive
   */
  public function __construct(Package $package, $recursive= false) {
    $this->package= $package;
    $this->recursive= $recursive;
  }

  /** @return iterable */
  private function classesIn($package) {
    foreach ($package->getClasses() as $class) {
      yield $class;
    }
    if ($this->recursive) {
      foreach ($package->getPackages() as $child) {
        foreach ($this->classesIn($child) as $class) {
          yield $class;
        }
      }
    }
  }

  /** @return iterable */
  public function classes() {
    return $this->classesIn($this->package);
  }

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

    foreach ($this->classesIn($this->package) as $class) {
      if ($class->isSubclassOf(TestCase::class) && !Modifiers::isAbstract($class->getModifiers())) {
        $suite->addTestClass($class, $arguments);
        $empty= false;
      }
    }

    if ($empty) {
      throw new IllegalArgumentException('Cannot find any test cases in '.$this->toString());
    }
  }

  /**
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->package->getName().($this->recursive ? '.**' : '.*').']';
  }
}
