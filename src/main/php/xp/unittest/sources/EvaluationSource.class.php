<?php namespace xp\unittest\sources;

use io\streams\StringReader;
use lang\ClassLoader;

/**
 * Source that dynamically creates testcases. Uses XP annotations for PHP 7
 * to retain maximum backwards compatibility!
 */
class EvaluationSource extends AbstractSource {
  private static $uniqId= 1;
  private static $annotation;
  private $class;

  static function __static() {
    self::$annotation= PHP_VERSION_ID < 80000 ? '#[@test]' : '#[Test]';
  }
  
  /**
   * Constructor
   *
   * @param  string|io.streams.StringReader $arg method sourcecode
   */
  public function __construct($arg) {
    if ($arg instanceof StringReader) {
      $input= '';
      while (null !== ($chunk= $arg->read())) {
        $input.= $chunk;
      }
    } else {
      $input= (string)$arg;
    }

    // Support <?php
    if (0 === strncmp($input, '<?', 2)) {
      $input= substr($input, strcspn($input, "\r\n\t =") + 1);
    }

    $this->class= ClassLoader::defineClass(
      'unittest.Evaluate'.(self::$uniqId++),
      null,
      [],
      "{\n\n  ".self::$annotation."\n  public function run() {\n    ".trim($input, "\r\n\t ;").";\n  }\n}"
    );
  }

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   */
  public function provideTo($suite, $arguments) {
    $suite->addTestClass($this->class);
  }

  /**
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->class->toString().']';
  }
}