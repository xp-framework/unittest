<?php namespace xp\unittest\sources;

use lang\ClassLoader;
use unittest\TestCase;

/**
 * Source that dynamically creates testcases
 */
class EvaluationSource extends AbstractSource {
  private static $uniqId= 0;
  private static $annotation;
  private $testClass;

  static function __static() {

    // Use XP annotations for PHP 7 to retain maximum backwards compatibility
    self::$annotation= PHP_VERSION_ID < 80000 ? '#[@test]' : '#[Test]';
  }
  
  /**
   * Constructor
   *
   * @param  string $src method sourcecode
   */
  public function __construct($input) {

    // Support <?php
    if (0 === strncmp($input, '<?', 2)) {
      $input= substr($input, strcspn($input, "\r\n\t =") + 1);
    }
    $fragment= trim($input, "\r\n\t ;").';';

    $name= 'xp.unittest.DynamicallyGeneratedTestCase'.(self::$uniqId++);
    $this->testClass= ClassLoader::defineClass($name, TestCase::class, [], '{
      '.self::$annotation.'
      public function run() { '.$fragment.' }
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