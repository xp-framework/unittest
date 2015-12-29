<?php namespace xp\unittest\sources;

use lang\reflect\Package;
use lang\reflect\Modifiers;
use unittest\TestCase;

/**
 * Source that load tests from a package
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

  /**
   * Provide tests from a given package to the test suite. Handles recursion.
   *
   * @param  lang.reflect.Package $package
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   */
  private function provideFrom($package, $suite, $arguments) {
    foreach ($package->getClasses() as $class) {
      if ($class->isSubclassOf(TestCase::class) && !Modifiers::isAbstract($class->getModifiers())) {
        $suite->addTestClass($class, $arguments);
      }
    }
    if ($this->recursive) {
      foreach ($package->getPackages() as $package) {
        $this->provideFrom($package, $suite, $arguments);
      }
    }
  }

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   */
  public function provideTo($suite, $arguments) {
    $this->provideFrom($this->package, $suite, $arguments);
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
