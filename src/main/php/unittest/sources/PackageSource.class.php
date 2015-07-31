<?php namespace xp\unittest\sources;

use lang\reflect\Package;
use lang\reflect\Modifiers;

/**
 * Source that load tests from a package
 */
class PackageSource extends AbstractSource {
  protected
    $package    = null,
    $recursive  = false;
  
  /**
   * Constructor
   *
   * @param   lang.reflect.Package package
   * @param   bool recursive default FALSE
   */
  public function __construct(Package $package, $recursive= false) {
    $this->package= $package;
    $this->recursive= $recursive;
  }
  
  /**
   * Returns a list of all classes inside a given package
   *
   * @param   lang.reflect.Package 
   * @param   bool recursive whether to include subpackages
   * @return  lang.XPClass[]
   */
  protected static function testClassesIn(Package $package, $recursive) {
    $r= [];
    foreach ($package->getClasses() as $class) {
      if (
        !$class->isSubclassOf('unittest.TestCase') ||
        Modifiers::isAbstract($class->getModifiers())
      ) continue;
      $r[]= $class;
    }
    if ($recursive) foreach ($package->getPackages() as $package) {
      $r= array_merge($r, self::testClassesIn($package, $recursive));
    }
    return $r;
  }

  /**
   * Get all test cases
   *
   * @param   var[] arguments
   * @return  unittest.TestCase[]
   */
  public function testCasesWith($arguments) {
    $tests= [];
    foreach (self::testClassesIn($this->package, $this->recursive) as $class) {
      $tests= array_merge($tests, $this->testCasesInClass($class, $arguments));
    }
    return $tests;
  }
  
  /**
   * Creates a string representation of this source
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'['.$this->package->getName().($this->recursive ? '.**' : '.*').']';
  }
}
