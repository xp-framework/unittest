<?php namespace unittest;

use lang\XPClass;
use lang\IllegalStateException;
use lang\IllegalArgumentException;

abstract class TestGroup extends \lang\Object {
  protected $before= [];
  protected $after= [];

  protected static $base;

  static function __static() {
    self::$base= new XPClass(TestCase::class);
  }

  /**
   * Returns actions
   *
   * @param  lang.XPClass|lang.reflect.Method $annotatable
   * @param  string $impl The interface which must've been implemented
   * @return var[]
   */
  protected function actionsFor($annotatable, $impl) {
    $r= [];
    if ($annotatable->hasAnnotation('action')) {
      $action= $annotatable->getAnnotation('action');
      $type= new XPClass($impl);
      if (is_array($action)) {
        foreach ($action as $a) {
          if ($type->isInstance($a)) $r[]= $a;
        }
      } else {
        if ($type->isInstance($action)) $r[]= $action;
      }
    }
    return $r;
  }

  /**
   * Handle special method
   *
   * @param  lang.reflect.Method
   * @param  lang.reflect.Method[] $before
   * @param  lang.reflect.Method[] $after
   * @return void
   */
  protected function withMethod($method, &$before, &$after) {
    $name= $method->getName();
    if ('setUp' === $name) {
      $before[$name]= function($object) use($method) { $method->invoke($object, []); };
      return;
    } else if ('tearDown' === $name) {
      $after[$name]= function($object) use($method) { $method->invoke($object, []); };
      return;
    }

    $annotations= $method->getAnnotations();
    if (array_key_exists('beforeClass', $annotations)) {
      $this->before[$name]= function($object) use($method) { $method->invoke($object, []); };
      return;
    } else if (array_key_exists('afterClass', $annotations)) {
      $this->after[$name]= function($object) use($method) { $method->invoke($object, []); };
      return;
    }
  }

  /**
   * Verify no special method, e.g. setUp() or tearDown() is overwritten.
   *
   * @param  lang.reflect.Method
   * @throws lang.IllegalStateException
   * @return void
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
  }

  /**
   * Sets up before and after for class based on `@action` annotation.
   *
   * @param  lang.XPClass
   * @throws lang.IllegalStateException
   * @return void
   */
  protected function setupClass($class) {
    foreach ($this->actionsFor($class, TestClassAction::class) as $pos => $action) {
      $key= nameof($action).'#'.$pos;
      $this->before[$key]= function() use($class, $action) { $action->beforeTestClass($class); };
      $this->after[$key]= function() use($class, $action) { $action->afterTestClass($class); };
    }
  }

  /** @return bool */
  public abstract function hasTests();

  /** @return int */
  public abstract function numTests();

  /** @return unittest.TestTarget[] */
  public abstract function targets();

  /** @return lang.reflect.Method[] */
  public function before() { return $this->before; }

  /** @return lang.reflect.Method[] */
  public function after() { return $this->after; }

  /** @return string */
  public function toString() {
    return nameof($this)."@{\n".
      "  before: ".($this->before ? \xp::stringOf($this->before, '  ') : '[]')."\n".
      "  targets: ".\xp::stringOf($this->targets(), '  ')."\n".
      "  after: ".($this->after ? \xp::stringOf($this->after, '  ') : '[]')."\n".
    "}";
  }
}