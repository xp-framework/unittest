<?php namespace unittest;

use lang\reflection\InvocationFailed;
use lang\{IllegalStateException, Reflection};

abstract class TestGroup {
  protected static $base;

  static function __static() {
    self::$base= Reflection::of(TestCase::class);
  }

  /**
   * Returns actions for a given class
   *
   * @param  lang.reflection.Type|lang.reflection.Method $annotated
   * @param  string $kind
   * @return iterable
   */
  protected function actionsFor($annotated, $kind) {

    // XP: [@action(new RuntimeVersion(...))]
    if ($annotation= $annotated->annotation(Action::class)) {
      $action= $annotation->argument(0);
      if (is_array($action)) {
        foreach ($action as $a) {
          if ($a instanceof $kind) yield $a;
        }
      } else {
        if ($action instanceof $kind) yield $action;
      }
    }

    // PHP: [RuntimeVersion(<const>), VerifyThat(eval: '<expr>')]
    foreach ($annotated->annotations() as $type => $annotation) {
      if ($annotation->is($kind)) {
        yield Reflection::of($type)->newInstance(...$annotation->arguments());
      }
    }
  }

  /** @return iterable */
  protected function beforeGroup() {
    foreach ($this->reflect()->methods() as $m) {
      if ($m->annotation(BeforeClass::class)) yield $m->name() => $m->invoke(null, []);
    }
  }

  /** @return iterable */
  protected function afterGroup() {
    foreach ($this->reflect()->methods() as $m) {
      if ($m->annotation(AfterClass::class)) yield $m->name() => $m->invoke(null, []);
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
      } catch (InvocationFailed $e) {
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

    $reflect= $this->reflect();
    $type= $reflect->class();
    foreach ($this->actionsFor($reflect, TestClassAction::class) as $action) {
      $action->beforeTestClass($type);
    }
  }

  /**
   * Runs actions after this group
   *
   * @return void
   */
  public function after() {
    $reflect= $this->reflect();
    $type= $reflect->class();
    foreach ($this->actionsFor($reflect, TestClassAction::class) as $action) {
      $action->afterTestClass($type);
    }

    $it= $this->afterGroup();
    do {
      try {
        $it->current();
      } catch (InvocationFailed $ignored) { }
      $it->next();
    } while ($it->valid());
  }

  /** @return lang.reflection.Type */
  public abstract function reflect();

  /** @return int */
  public abstract function numTests();

  /** @return php.Generator */
  public abstract function tests();
}