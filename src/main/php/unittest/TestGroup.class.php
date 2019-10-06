<?php namespace unittest;

use lang\IllegalStateException;
use lang\XPClass;
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
   * Returns actions for a given class
   *
   * @param  lang.XPClass $class
   * @param  string $kind
   * @return iterable
   */
  protected function actionsFor($class, $kind) {
    if ($class->hasAnnotation('action')) {
      $action= $class->getAnnotation('action');
      if (is_array($action)) {
        foreach ($action as $a) {
          if ($a instanceof $kind) yield $a;
        }
      } else {
        if ($action instanceof $kind) yield $action;
      }
    }
  }

  /** @return iterable */
  protected function beforeGroup() {
    foreach ($this->type()->getMethods() as $m) {
      if ($m->hasAnnotation('beforeClass')) yield $m->getName() => $m->invoke(null, []);
    }
  }

  /** @return iterable */
  protected function afterGroup() {
    foreach ($this->type()->getMethods() as $m) {
      if ($m->hasAnnotation('afterClass')) yield $m->getName() => $m->invoke(null, []);
    }
  }

  /**
   * Runs actions before this group
   *
   * @return void
   * @throws unittest.PrerequisitesNotMetError
   */
  public function before() {
    $it= $this->beforeGroup();
    do {
      try {
        $it->current();
      } catch (TargetInvocationException $e) {
        $cause= $e->getCause();
        if ($cause instanceof PrerequisitesNotMetError) {
          throw $cause;
        } else {
          $name= substr(strstr($e->getMessage(), '::'), 2);
          throw new PrerequisitesNotMetError('Exception in beforeClass method '.$name, $cause);
        }
      }
      $it->next();
    } while ($it->valid());

    $class= $this->type();
    foreach ($this->actionsFor($class, TestClassAction::class) as $action) {
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
    foreach ($this->actionsFor($class, TestClassAction::class) as $action) {
      $action->afterTestClass($class);
    }

    $it= $this->afterGroup();
    do {
      try {
        $it->current();
      } catch (TargetInvocationException $ignored) { }
      $it->next();
    } while ($it->valid());
  }

  /** @return lang.XPClass */
  public abstract function type();

  /** @return int */
  public abstract function numTests();

  /** @return php.Generator */
  public abstract function tests();
}