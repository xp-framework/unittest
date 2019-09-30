<?php namespace unittest;

use lang\Throwable;
use lang\XPClass;
use lang\reflect\TargetInvocationException;
use util\profiling\Timer;

class TestRun {
  private $result, $listeners;

  /**
   * Creates a new run
   *
   * @param  unittest.TestResult $result
   * @param  unittest.TestListener[] $listeners
   */
  public function __construct(TestResult $result, $listeners) {
    $this->result= $result;
    $this->listeners= $listeners;
  }

  /** @return unittest.TestResult */
  public function result() { return $this->result; }

  /** @return unittest.TestListener[] */
  public function listeners() { return $this->listeners; }

  /**
   * Notify listeners
   *
   * @param  string $method
   * @param  var[] $args
   * @return void
   */
  private function notify($method, $args) {
    foreach ($this->listeners as $l) {
      $l->{$method}(...$args);
    }
  }

  /**
   * Returns values
   *
   * @param  object $test
   * @param  var $annotation
   * @return var values a traversable structure
   */
  private function valuesFor($test, $annotation) {
    if (!is_array($annotation)) {               // values("source")
      $source= $annotation;
      $args= [];
    } else if (isset($annotation['map'])) {     // values(map= ["test" => true, ...])
      $values= [];
      foreach ($annotation['map'] as $key => $value) {
        $values[]= [$key, $value];
      }
      return $values;
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
   * @param  var $annotatable
   * @return var[]
   */
  private function actionsFor($annotatable) {
    $r= [];
    if ($annotatable->hasAnnotation('action')) {
      $action= $annotatable->getAnnotation('action');
      if (is_array($action)) {
        foreach ($action as $a) {
          if ($a instanceof TestAction) $r[]= $a;
        }
      } else {
        if ($action instanceof TestAction) $r[]= $action;
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
  private function record($type, $outcome) {
    $this->notify($type, [$this->result->record($outcome)]);
    Errors::clear();
  }

  /**
   * Run a test case.
   *
   * @param  unittest.Test $test
   * @param  unittest.TestResult $result
   * @return void
   */
  private function run($test) {
    $this->notify('testStarted', [$test]);

    // Check for @ignore
    if ($test->method->hasAnnotation('ignore')) {
      $this->record('testNotRun', new TestNotRun($test, new IgnoredBecause($test->method->getAnnotation('ignore'))));
      return;
    }

    // Check for @expect
    $expected= null;
    if ($test->method->hasAnnotation('expect', 'class')) {
      $message= $test->method->getAnnotation('expect', 'withMessage');
      if ('' === $message || '/' === $message{0}) {
        $pattern= $message;
      } else {
        $pattern= '/'.preg_quote($message, '/').'/';
      }
      $expected= [XPClass::forName($test->method->getAnnotation('expect', 'class')), $pattern];
    } else if ($test->method->hasAnnotation('expect')) {
      $expected= [XPClass::forName($test->method->getAnnotation('expect')), null];
    }
    
    // Check for @limit
    $eta= 0;
    if ($test->method->hasAnnotation('limit')) {
      $eta= $test->method->getAnnotation('limit', 'time');
    }

    // Check for @values
    if ($test->method->hasAnnotation('values')) {
      $annotation= $test->method->getAnnotation('values');
      $variation= true;
      $values= $this->valuesFor($test->instance, $annotation);
    } else {
      $variation= false;
      $values= [[]];
    }

    // Check for @actions
    $actions= array_merge($this->actionsFor(typeof($test->instance)), $this->actionsFor($test->method));

    $timer= new Timer();
    Errors::clear();
    foreach ($values as $args) {
      $t= $variation ? new TestVariation($test, $args) : $test;
      $timer->start();

      $tearDown= function($test, $error) { return $error; };
      try {

        // Before and after tests
        foreach ($actions as $action) {
          $this->invoke([$action, 'beforeTest'], $test->asCase());
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

        $test->run(is_array($args) ? $args : [$args]);
        $thrown= $tearDown($test->asCase(), null);
      } catch (TestAborted $aborted) {
        $tearDown($test->asCase(), $aborted);
        $this->record($aborted->type(), $aborted->outcome($t, $timer));
        continue;
      } catch (TargetInvocationException $invoke) {
        $thrown= $tearDown($test->asCase(), $invoke->getCause());
      } catch (Throwable $error) {
        $thrown= $tearDown($test->asCase(), $error);
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
   * Starts a suite
   *
   * @param  unittest.TestSuite $suite
   * @return void
   */
  public function start(TestSuite $suite) {
    $this->notify('testRunStarted', [$suite]);
  }

  /**
   * Finishes a suite
   *
   * @param  unittest.TestSuite $suite
   * @return void
   */
  public function finish(TestSuite $suite) {
    $this->notify('testRunFinished', [$suite, $this->result, null]);
  }

  /**
   * Aborts a suite
   *
   * @param  unittest.TestSuite $suite
   * @param  unittest.StopTests $reason
   * @return void
   */
  public function abort(TestSuite $suite, StopTests $reason) {
    $this->notify('testRunFinished', [$suite, $this->result, $reason]);
  }

  /**
   * Runs a single test group
   *
   * @param  unittest.TestGroup $group
   * @return void
   */
  public function one(TestGroup $group) {
    try {
      $group->before();
    } catch (PrerequisitesNotMetError $e) {
      $timer= new Timer();
      foreach ($group->targets() as $test) {
        $this->record($e->type(), $e->outcome($test, $timer));
      }
      return;
    }

    foreach ($group->targets() as $test) {
      $this->run($test);
    }
    $group->after();
  }

  /**
   * Runs test groups
   *
   * @param  unittest.TestGroup[] $groups
   * @return void
   */
  public function all($groups) {
    foreach ($groups as $group) {
      $this->one($group);
    }
  }
}