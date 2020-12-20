<?php namespace xp\unittest\sources;

use lang\reflection\Package;
use lang\{Reflection, IllegalArgumentException};

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
   * @param  lang.reflection.Package $package
   * @param  bool $recursive
   * @throws lang.IllegalArgumentException if the package does not exist
   */
  public function __construct(Package $package, $recursive= false) {
    if (0 === iterator_count($package->classLoaders())) {
      throw new IllegalArgumentException('No classloaders provide '.$package->name());
    }

    $this->package= $package;
    $this->recursive= $recursive;
  }

  /** @return iterable */
  private function classesIn($package) {
    foreach ($package->types() as $type) {
      yield $type;
    }
    if ($this->recursive) {
      foreach ($package->children() as $child) {
        yield from $this->classesIn($child);
      }
    }
  }

  /** @return iterable */
  protected function classes() {
    return $this->classesIn($this->package);
  }

  /**
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->package->name().($this->recursive ? '.**' : '.*').']';
  }
}