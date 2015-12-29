<?php namespace xp\unittest\sources;

use lang\ClassLoader;
use unittest\TestCase;

/**
 * Source that dynamically creates testcases
 */
class EvaluationSource extends AbstractSource {
  private static $uniqId= 0;
  private $testClass;
  
  /**
   * Constructor
   *
   * @param  string $src method sourcecode
   */
  public function __construct($src) {

    // Support <?php
    $src= trim($src, ' ;').';';
    if (0 === strncmp($src, '<?php', 5)) {
      $src= substr($src, 6);
    }

    $name= 'xp.unittest.DynamicallyGeneratedTestCase'.(self::$uniqId++);
    $this->testClass= ClassLoader::defineClass($name, TestCase::class, [], '{
      #[@test] 
      public function run() { '.$src.' }
    }');
  }

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   */
  public function provideTo($suite, $arguments) {
    $suite->addTest($this->testClass->newInstance('run'));
  }

  /**
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->testClass->toString().']';
  }
}
