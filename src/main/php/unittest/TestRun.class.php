<?php namespace unittest;

use lang\reflect\TargetInvocationException;
use lang\Throwable;
use lang\XPClass;
use util\profiling\Timer;

class TestRun {
  private $result, $listeners;

  public function __construct($result, $listeners) {
    $this->result= $result;
    $this->listeners= $listeners;
  }

  /**
   * Notify listeners
   *
   * @param  string $method
   * @param  var[] $args
   * @return void
   */
  public function notify($method, $args) {
    foreach ($this->listeners as $l) {
      $l->{$method}(...$args);
    }
  }

  /**
   * Call beforeClass methods if present. If any of them throws an exception,
   * mark all tests in this class as skipped and continue with tests from
   * other classes (if available)
   *
   * @param  lang.XPClass class
   * @return void
   */
  protected function beforeClass($class) {
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
    foreach ($this->actionsFor($class, TestClassAction::class) as $action) {
      $action->beforeTestClass($class);
    }
  }
  
  /**
   * Call afterClass methods of the last test's class. Ignore any 
   * exceptions thrown from these methods.
   *
   * @param  lang.XPClass class
   * @return void
   */
  protected function afterClass($class) {
    foreach ($this->actionsFor($class, TestClassAction::class) as $action) {
      $action->afterTestClass($class);
    }
    foreach ($class->getMethods() as $m) {
      if (!$m->hasAnnotation('afterClass')) continue;
      try {
        $m->invoke(null, []);
      } catch (TargetInvocationException $ignored) { }
    }
  }

 /**
   * Returns values
   *
   * @param  unittest.TestCase test
   * @param  var annotation
   * @return var values a traversable structure
   */
  protected function valuesFor($test, $annotation) {
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
      return typeof($test)->getMethod($source)->setAccessible(true)->invoke($test, $args);
    }

    $ref= substr($source, 0, $p);
    if ('self' === $ref) {
      $class= typeof($test);
    } else if (strstr($ref, '.')) {
      $class= XPClass::forName($ref);
    } else {
      $class= new XPClass($ref);
    }
    return $class->getMethod(substr($source, $p+ 2))->invoke(null, $args);
  }

  /**
   * Returns values
   *
   * @param  var annotatable
   * @param  string impl The interface which must've been implemented
   * @return var[]
   */
  protected function actionsFor($annotatable, $impl) {
    $r= [];
    if ($annotatable->hasAnnotation('action')) {
      $action= $annotatable->getAnnotation('action');
      $type= XPClass::forName($impl);
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
   * Invoke a block, wrap PHP5 and PHP7 native base exceptions in lang.Error
   *
   * @param  function(?): void $block
   * @param  var $arg
   * @return void
   */
  private function invoke($block, $arg) {
    try {
      $block($arg);
    } catch (Throwable $e) {
      throw $e;
    } catch (\Exception $e) {
      throw Throwable::wrap($e);
    } catch (\Throwable $e) {
      throw Throwable::wrap($e);
    }
  }

  /**
   * Record outcome, notifying listeners
   *
   * @param  string $type
   * @param  unittest.TestOutcome $result
   * @return void
   */
  protected function record($type, $outcome) {
    $this->notify($type, [$this->result->record($outcome)]);
    Errors::clear();
  }

  /**
   * Run a test case.
   *
   * @param  unittest.TestCase $test
   * @param  unittest.TestResult $result
   * @return void
   */
  protected function run($test) {
    $class= typeof($test);
    $method= $class->getMethod($test->name);
    $this->notify('testStarted', [$test]);
    
    // Check for @ignore
    if ($method->hasAnnotation('ignore')) {
      $this->record('testNotRun', new TestNotRun($test, new IgnoredBecause($method->getAnnotation('ignore'))));
      return;
    }

    // Check for @expect
    $expected= null;
    if ($method->hasAnnotation('expect', 'class')) {
      $message= $method->getAnnotation('expect', 'withMessage');
      if ('' === $message || '/' === $message{0}) {
        $pattern= $message;
      } else {
        $pattern= '/'.preg_quote($message, '/').'/';
      }
      $expected= [XPClass::forName($method->getAnnotation('expect', 'class')), $pattern];
    } else if ($method->hasAnnotation('expect')) {
      $expected= [XPClass::forName($method->getAnnotation('expect')), null];
    }
    
    // Check for @limit
    $eta= 0;
    if ($method->hasAnnotation('limit')) {
      $eta= $method->getAnnotation('limit', 'time');
    }

    // Check for @values
    if ($method->hasAnnotation('values')) {
      $annotation= $method->getAnnotation('values');
      $variation= true;
      $values= $this->valuesFor($test, $annotation);
    } else {
      $variation= false;
      $values= [[]];
    }

    // Check for @actions
    $actions= array_merge(
      $this->actionsFor($class, TestAction::class),
      $this->actionsFor($method, TestAction::class)
    );

    $timer= new Timer();
    Errors::clear();
    foreach ($values as $args) {
      $t= $variation ? new TestVariation($test, $args) : $test;
      $timer->start();

      $tearDown= function($test, $error) { return $error; };
      try {

        // Before and after tests
        foreach ($actions as $action) {
          $this->invoke([$action, 'beforeTest'], $test);
          $tearDown= function($test, $error) use($tearDown, $action) {
            $propagated= $tearDown($test, $error);
            try {
              $this->invoke([$action, 'afterTest'], $test);
              return $propagated;
            } catch (Throwable $t) {
              $propagated && $t->setCause($propagated);
              return $t;
            }
          };
        }

        // Setup and teardown
        $this->invoke([$test, 'setUp'], $test);
        $tearDown= function($test, $error) use($tearDown) {
          try {
            $this->invoke([$test, 'tearDown'], null);
            return $tearDown($test, $error);
          } catch (Throwable $t) {
            $error && $t->setCause($error);
            return $tearDown($test, $t);
          }
        };

        // Run test
        $method->invoke($test, is_array($args) ? $args : [$args]);
        $thrown= $tearDown($test, null);
      } catch (TestAborted $aborted) {
        $tearDown($test, $aborted);
        $this->record($aborted->type(), $aborted->outcome($t, $timer));
        continue;
      } catch (TargetInvocationException $invoke) {
        $thrown= $tearDown($test, $invoke->getCause());
      } catch (Throwable $error) {
        $thrown= $tearDown($test, $error);
      }

      // Check outcome
      $time= $timer->elapsedTime();
      if ($eta && $time > $eta) {
        $this->record('testFailed', new TestAssertionFailed($t, new TimedOut($eta, $time), $time));
      } else if ($thrown) {
        if ($expected && $expected[0]->isInstance($thrown)) {
          if ($expected[1] && !preg_match($expected[1], $thrown->getMessage())) {
            $this->record('testFailed', new TestAssertionFailed($t, new ExpectedMessageDiffers($expected[1], $thrown), $time));
          } else if ($errors= Errors::raised()) {
            $this->record('testWarning', new TestWarning($t, $errors, $time));
          } else {
            $this->record('testSucceeded', new TestExpectationMet($t, $time));
          }
        } else if ($expected && !$expected[0]->isInstance($thrown)) {
          $this->record('testFailed', new TestAssertionFailed($t, new DidNotCatch($expected[0], $thrown), $time));
        } else if ($thrown instanceof TestAborted) {
          $this->record($thrown->type(), $thrown->outcome($t, $timer));
        } else {
          $this->record('testError', new TestError($t, $thrown, $time));
        }
      } else if ($expected) {
        $this->record('testFailed', new TestAssertionFailed($t, new DidNotCatch($expected[0]), $time));
      } else if ($errors= Errors::raised()) {
        $this->record('testWarning', new TestWarning($t, $errors, $time));
      } else {
        $this->record('testSucceeded', new TestExpectationMet($t, $time));
      }
    }
  }

  /**
   * Runs a single test group
   *
   * @param  unittest.TestGroup $group
   * @return void
   */
  public function one(TestGroup $group) {
    $class= $group->type();

    try {
      $this->beforeClass($class);
    } catch (PrerequisitesNotMetError $e) {
      foreach ($group->tests() as $test) {
        $this->record('testSkipped', new TestPrerequisitesNotMet($test, $e, 0.0));
      }
      return;
    }

    foreach ($group->tests() as $test) {
      $this->run($test);
    }
    $this->afterClass($class);
  }

  /**
   * Runs test groups
   *
   * @param  [:unittest.TestGroup[]] $sources
   * @return void
   */
  public function all($sources) {
    foreach ($sources as $classname => $groups) {
      $class= new XPClass($classname);

      try {
        $this->beforeClass($class);
      } catch (PrerequisitesNotMetError $e) {
        foreach ($groups as $group) {
          foreach ($group->tests() as $test) {
            $this->record('testSkipped', new TestPrerequisitesNotMet($test, $e, 0.0));
          }
        }
        continue;
      }

      foreach ($groups as $group) {
        foreach ($group->tests() as $test) {
          $this->run($test);
        }
      }
      $this->afterClass($class);
    }
  }
}