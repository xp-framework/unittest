<?php namespace unittest;

use lang\XPClass;
use lang\IllegalStateException;

class TestTarget extends \lang\Object {
  private $instance, $ignored;
  private $before, $after;

  protected static $base;

  static function __static() {
    self::$base= new XPClass(TestCase::class);
  }

  public function __construct($instance, $method, &$before, &$after) {
    $this->instance= $instance;
    $this->setupMethod($method);
    $this->before= &$before;
    $this->after= &$after;
  }

  /**
   * Verify no special method, e.g. setUp() or tearDown() is overwritten.
   *
   * @param  lang.reflect.Method
   * @throws lang.IllegalStateException
   * @return var
   */
  protected function setupMethod($method) {
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
    } else {
      $this->ignored= null; 
    }
  }

  public function instance() { return $this->instance; }

  public function ignored() { return $this->ignored; }

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