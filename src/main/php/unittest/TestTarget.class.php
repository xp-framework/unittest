<?php namespace unittest;

use lang\XPClass;
use lang\IllegalStateException;

class TestTarget extends \lang\Object {
  private $instance, $method;
  private $ignored= null, $expects= null, $limit= null, $variations= [], $actions= [];
  private $before, $after;

  private static $base;

  static function __static() {
    self::$base= new XPClass(TestCase::class);
  }

  public function __construct($instance, $method, &$before, &$after) {
    $this->instance= $instance;
    $this->method= $method;
    $this->before= &$before;
    $this->after= &$after;

    $this->setupMethod($method);
  }

  /**
   * Returns values
   *
   * @param  unittest.TestCase test
   * @param  var annotation
   * @return var values a traversable structure
   */
  private function valuesFor($test, $annotation) {
    if (!is_array($annotation)) {               // values("source")
      $source= $annotation;
      $args= [];
    } else if (isset($annotation['source'])) {  // values(source= "src" [, args= ...])
      $source= $annotation['source'];
      $args= isset($annotation['args']) ? $annotation['args'] : [];
    } else {                                    // values([1, 2, 3])
      return $annotation;
    }

    // Route "ClassName::methodName" -> static method of the given class,
    // "self::method" -> static method of the test class, and "method" 
    // -> the run test's instance method
    if (false === ($p= strpos($source, '::'))) {
      return $test->getClass()->getMethod($source)->setAccessible(true)->invoke($test, $args);
    }

    $ref= substr($source, 0, $p);
    if ('self' === $ref) {
      $class= $test->getClass();
    } else if (strstr($ref, '.')) {
      $class= XPClass::forName($ref);
    } else {
      $class= new XPClass($ref);
    }
    return $class->getMethod(substr($source, $p+ 2))->invoke(null, $args);
  }

  /**
   * Verify no special method, e.g. setUp() or tearDown() is overwritten.
   *
   * @param  lang.reflect.Method
   * @throws lang.IllegalStateException
   * @return var
   */
  private function setupMethod($method) {
    if (self::$base->hasMethod($method->getName())) {
      throw new IllegalStateException(sprintf(
        'Cannot override %s::%s with test method in %s',
        self::$base->getName(),
        $method->getName(),
        $method->getDeclaringClass()->getName()
      ));
    }

    if ($method->hasAnnotation('ignore')) {
      $this->ignored= ['reason' => $method->getAnnotation('ignore')];
    }

    // Check for @expect
    if ($method->hasAnnotation('expect', 'class')) {
      $message= $method->getAnnotation('expect', 'withMessage');
      if ('/' === $message{0}) {
        $pattern= $message;
      } else {
        $pattern= '/'.preg_quote($message, '/').'/';
      }
      $this->expects= [XPClass::forName($method->getAnnotation('expect', 'class')), $pattern];
    } else if ($method->hasAnnotation('expect')) {
      $this->expects= [XPClass::forName($method->getAnnotation('expect')), null];
    }

    // Check for @limit
    if ($method->hasAnnotation('limit')) {
      $this->limit= $method->getAnnotation('limit');
    }

    // Check for @values
    if ($method->hasAnnotation('values')) {
      $this->variations= $method->getAnnotation('values');
    }

    // Class actions
    $class= typeof($this->instance);
    if ($class->hasAnnotation('action')) {
      $annotation= $class->getAnnotation('action');
      $actions= is_array($annotation) ? $annotation : [$annotation];
      foreach ($actions as $action) {
        if ($action instanceof TestAction) {
          $this->actions[]= $action;
        }
      }
    }

    // Method actions
    if ($method->hasAnnotation('action')) {
      $annotation= $method->getAnnotation('action');
      $actions= is_array($annotation) ? $annotation : [$annotation];
      foreach ($actions as $action) {
        $this->actions[]= $action;
      }
    }
  }

  /** @return unittest.TestCase */
  public function instance() { return $this->instance; }

  public function method() { return $this->method; }

  public function ignored() { return $this->ignored; }

  public function expects() { return $this->expects; }

  public function limit() { return $this->limit; }

  /** @return php.Generator */
  public function variations() {
    if ($this->variations) {
      foreach ($this->valuesFor($this->instance, $this->variations) as $args) {
        yield [new TestVariation($this->instance, $args), $args];
      }
    } else {
      yield [$this->instance, []];
    }
  }

  public function actions() { return $this->actions; }

  public function before() { return $this->before; }

  public function after() { return $this->after; }

  public function toString() {
    return nameof($this).'@('.$this->method."){\n".
      "  before: ".($this->before ? \xp::stringOf($this->before, '  ') : '[]')."\n".
      "  test: ".$this->instance->toString().")\n".
      "  after: ".($this->after ? \xp::stringOf($this->after, '  '): '[]')."\n".
    "}";
  }
}