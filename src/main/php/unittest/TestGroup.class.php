<?php namespace unittest;

use lang\{IllegalStateException, XPClass};
use lang\reflect\TargetInvocationException;

abstract class TestGroup {
  protected static $base;

  static function __static() {
    self::$base= new XPClass(TestCase::class);
  }

  /**
   * Verify test method doesn't override a special method from TestCase
   *
   * @param  lang.reflect.Method $method
   * @return lang.IllegalStateException
   */
  protected function cannotOverride($method) {
    return new IllegalStateException(sprintf(
      'Cannot override %s::%s with test method in %s',
      self::$base->getName(),
      $method->getName(),
      $method->getDeclaringClass()->getName()
    ));
  }

  /**
   * Returns TestClassActions for a given class
   *
   * @param  lang.XPClass $class
   * @return iterable
   */
  private function actionsFor($class) {
    if ($class->hasAnnotation('action')) {
      $action= $class->getAnnotation('action');
      if (is_array($action)) {
        foreach ($action as $a) {
          if ($a instanceof TestClassAction) yield $a;
        }
      } else {
        if ($action instanceof TestClassAction) yield $action;
      }
    }
  }

  /**
   * Runs actions before this group
   *
   * @return void
   * @throws unittest.PrerequisitesNotMetError
   */
  public function before() {
    $class= $this->type();
    foreach ($class->getMethods() as $m) {
      if (!$m->hasAnnotation('beforeClass')) continue;
      try {
        $m->invoke(null, []);
      } catch (TargetInvocationException $e) {
        $cause= $e->getCause();
        if ($cause instanceof PrerequisitesNotMetError) {
          throw $cause;
        } else {
          throw new PrerequisitesNotMetError('Exception in beforeClass method '.$m->getName(), $cause);
        }
      }
    }
    foreach ($this->actionsFor($class) as $action) {
      $action->beforeTestClass($class);
    }
  }

  /**
   * Runs actions after this group
   *
   * @return void
   */
  public function after() {
    $class= $this->type();
    foreach ($this->actionsFor($class) as $action) {
      $action->afterTestClass($class);
    }
    foreach ($class->getMethods() as $m) {
      if (!$m->hasAnnotation('afterClass')) continue;
      try {
        $m->invoke(null, []);
      } catch (TargetInvocationException $ignored) { }
    }
  }

  /** @return lang.XPClass */
  public abstract function type();

  /** @return int */
  public abstract function numTests();

  /** @return php.Generator */
  public abstract function tests();
}