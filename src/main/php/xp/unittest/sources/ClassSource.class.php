<?php namespace xp\unittest\sources;

use lang\XPClass;
use unittest\{TestCase, TestMethod};

/**
 * Source that load tests from a class filename
 */
class ClassSource extends AbstractSource {
  private $testClass, $method;
  
  /**
   * Constructor
   *
   * @param  lang.XPClass $testClass
   * @param  string $method
   */
  public function __construct(XPClass $testClass, $method= null) {
    $this->testClass= $testClass;
    $this->method= $method;
  }

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   */
  public function provideTo($suite, $arguments) {
    if (null === $this->method) {
      return $suite->addTestClass($this->testClass, $arguments);
    } else if ($this->testClass->isSubclassOf(TestCase::class)) {
      $suite->addTest($this->testClass->getConstructor()->newInstance(array_merge(
        [$this->method],
        (array)$arguments
      )));
    } else {
      $suite->addTest(new TestMethod($this->testClass, $this->method, (array)$arguments));
    }
  }

  /**
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->testClass->toString().($this->method ? '::'.$this->method : '').']';
  }
}