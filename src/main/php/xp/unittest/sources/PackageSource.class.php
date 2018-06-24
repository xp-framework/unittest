<?php namespace xp\unittest\sources;

use lang\IllegalArgumentException;
use lang\reflect\Package;

/**
 * Source that load tests from a package
 *
 * @test  xp://unittest.tests.PackageSourceTest
 */
class PackageSource extends ClassesSource {
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
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->package->getName().($this->recursive ? '.**' : '.*').']';
  }
}
