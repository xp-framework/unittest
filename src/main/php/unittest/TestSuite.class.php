<?php namespace unittest;

use util\profiling\Timer;
use util\NoSuchElementException;
use lang\MethodNotImplementedException;
use lang\IllegalStateException;
use lang\IllegalArgumentException;
use lang\XPClass;
use lang\Throwable;
use lang\Error;
use lang\reflect\TargetInvocationException;

/**
 * Test suite
 *
 * @test   xp://net.xp_framework.unittest.tests.SuiteTest
 * @test   xp://net.xp_framework.unittest.tests.ListenerTest
 * @test   xp://net.xp_framework.unittest.tests.BeforeAndAfterClassTest
 * @see    http://junit.sourceforge.net/doc/testinfected/testing.htm
 */
class TestSuite extends \lang\Object {
  private $groups= [];
  private $numTests= 0;
  protected $listeners= [];

  /**
   * Add a test
   *
   * @param   unittest.TestCase test
   * @return  unittest.TestCase
   * @throws  lang.IllegalArgumentException in case given argument is not a testcase
   * @throws  lang.IllegalStateException for overriding test class methods with tests
   * @throws  lang.MethodNotImplementedException in case given argument is not a valid testcase
   */
  public function addTest(TestCase $test) {
    $this->groups[]= new TestInstance($test);
    $this->numTests++;
    return $test;
  }

  /**
   * Add a test class
   *
   * @param   lang.XPClass<unittest.TestCase> class
   * @param   var[] arguments default [] arguments to pass to test case constructor
   * @return  lang.reflect.Method[] ignored test methods
   * @throws  lang.IllegalArgumentException in case given argument is not a testcase class
   * @throws  util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function addTestClass($class, $arguments= []) {
    $target= new TestClass($class, $arguments);
    if (!$target->hasTests()) {
      throw new NoSuchElementException('No tests found in '.$class->getName());
    }

    $this->groups[]= $target;
    $this->numTests+= $target->numTests();
    return $class;
  }
  
  /**
   * Returns number of tests in this suite
   *
   * @return  int
   */
  public function numTests() {
    return $this->numTests;
  }
  
  /**
   * Remove all tests
   *
   * @return  void
   */
  public function clearTests() {
    $this->groups= [];
    $this->numTests= 0;
  }
  
  /**
   * Returns test at a given position
   *
   * @param   int pos
   * @return  unittest.TestCase or NULL if none was found
   */
  public function testAt($pos) {
    $i= 0;
    foreach ($this->groups as $group) {
      foreach ($group->targets() as $target) {
        if ($i++ === $pos) return $target->instance();
      }
    }
    return null;
  }
  
  /**
   * Adds a listener
   *
   * @param   unittest.TestListener l
   * @return  unittest.TestListener the added listener
   */
  public function addListener(TestListener $l) {
    $this->listeners[]= $l;
    return $l;
  }

  /**
   * Removes a listener
   *
   * @param   unittest.TestListener l
   * @return  bool TRUE if the listener was removed, FALSE if not.
   */
  public function removeListener(TestListener $l) {
    for ($i= 0, $s= sizeof($this->listeners); $i < $s; $i++) {
      if ($this->listeners[$i] !== $l) continue;

      // Found the listener, remove it and re-index the listeners array
      unset($this->listeners[$i]);
      $this->listeners= array_values($this->listeners);
      return true;
    }
    return false;
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
   * Format errors from xp registry
   *
   * @param   [:string[]] registry
   * @return  string[]
   */
  protected function formatErrors($registry) {
    $w= [];
    foreach ($registry as $file => $lookup) {
      foreach ($lookup as $line => $messages) {
        foreach ($messages as $message => $detail) {
          $w[]= sprintf(
            '"%s" in %s::%s() (%s, line %d, occured %s)',
            $message,
            $detail['class'],
            $detail['method'],
            basename($file),
            $line,
            1 === $detail['cnt'] ? 'once' : $detail['cnt'].' times'
          );
        }
      }
    }
    return $w;
  }
  
  /**
   * Notify listeners
   *
   * @param  string $method
   * @param  var[] $args
   * @return void
   */
  protected function notifyListeners($method, $args) {
    foreach ($this->listeners as $l) {
      call_user_func_array([$l, $method], $args);
    }
  }

  /**
   * Skip all targets
   *
   * @param  unittest.TestResult $result
   * @param  unittest.TestTarget[] $targets
   * @param  unittest.PrerequisitesNotMetError $error
   */
  protected function skipAll($result, $targets, $error) {
    foreach ($targets as $target) {
      $this->notifyListeners('testSkipped', [$result->setSkipped($target->instance(), $error, 0.0)]);
    }
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
    } catch (TargetInvocationException $e) {
      throw $e->getCause();
    } catch (Throwable $e) {
      throw $e;
    } catch (\Exception $e) {
      throw Throwable::wrap($e);
    } catch (\Throwable $e) {
      throw Throwable::wrap($e);
    }
  }

  /**
   * Runs a test target
   *
   * @param  unittest.TestTarget $target
   * @param  unittest.TestResult $result
   * @return void
   */
  protected function runTarget($target, $result) {
    $test= $target->instance();

    $this->notifyListeners('testStarted', [$test]);

    if ($ignored= $target->ignored()) {
      $this->notifyListeners('testNotRun', [$result->set($test, new TestNotRun($test, $ignored['reason']))]);
      return;
    }

    // Check for @actions
    $actions= array_merge(
      $this->actionsFor($test->getClass(), 'unittest.TestAction'),
      $this->actionsFor($target->method(), 'unittest.TestAction')
    );

    $timer= new Timer();
    $report= function($type, $outcome, $arg) use($result, $timer, &$t) {
      $timer->stop();
      $this->notifyListeners($type, [$result->set($t, new $outcome($t, $arg, $timer->elapsedTime()))]);
      \xp::gc();
    };
    \xp::gc();
    foreach ($target->variations() as $variation) {
      $t= $variation[0];
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
        foreach ($target->before() as $before) {
          $this->invoke($before, $test);
        }
        $tearDown= function($test, $error) use($target, $tearDown) {
          try {
            foreach ($target->after() as $after) {
              $this->invoke($after, $test);
            }
            return $tearDown($test, $error);
          } catch (Throwable $t) {
            $error && $t->setCause($error);
            return $tearDown($test, $t);
          }
        };

        // Run test
        $target->method()->invoke($test, $variation[1]);
        $e= $tearDown($test, null);
      } catch (TargetInvocationException $invoke) {
        $e= $tearDown($test, $invoke->getCause());
      } catch (PrerequisitesNotMetError $skipped) {
        $tearDown($test, $skipped);
        $report('testSkipped', TestPrerequisitesNotMet::class, $skipped);
        continue;
      } catch (AssertionFailedError $failed) {
        $tearDown($test, $failed);
        $report('testFailed', TestAssertionFailed::class, $failed);
        continue;
      } catch (Throwable $error) {
        $tearDown($test, $error);
        $report('testError', TestError::class, $error);
        continue;
      }

      $timer->stop();

      $expected= $target->expects();
      $eta= $target->limit();

      if ($e) {
        if ($expected && $expected[0]->isInstance($e)) {
          if ($eta && $timer->elapsedTime() > $eta['time']) {
            $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
              'Test runtime of %.3f seconds longer than eta of %.3f seconds',
              [$timer->elapsedTime(), $eta['time']]
            )));
          } else if ($expected[1] && !preg_match($expected[1], $e->getMessage())) {
            $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
              'Expected %s\'s message "%s" differs from expected %s',
              [nameof($e), $e->getMessage(), $expected[1]]
            )));
          } else if (sizeof(\xp::$errors) > 0) {
            $report('testWarning', TestWarning::class, $this->formatErrors(\xp::$errors));
          } else {
            $this->notifyListeners('testSucceeded', [$result->setSucceeded($t, $timer->elapsedTime())]);
          }
        } else if ($expected && !$expected[0]->isInstance($e)) {
          $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
            'Caught %s instead of expected %s',
            [$e->compoundMessage(), $expected[0]->getName()]
          )));
        } else if ($e instanceof AssertionFailedError) {
          $report('testFailed', TestAssertionFailed::class, $e);
        } else if ($e instanceof PrerequisitesNotMetError) {
          $report('testSkipped', TestPrerequisitesNotMet::class, $e);
        } else {
          $report('testError', TestError::class, $e);
        }
      } else if ($expected) {
        $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
          'Expected %s not caught',
          [$expected[0]->getName()]
        )));
      } else if (sizeof(\xp::$errors) > 0) {
        $report('testWarning', TestWarning::class, $this->formatErrors(\xp::$errors));
      } else if ($eta && $timer->elapsedTime() > $eta['time']) {
        $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
          'Test runtime of %.3f seconds longer than eta of %.3f seconds',
          [$timer->elapsedTime(), $eta['time']]
        )));
      } else {
        $this->notifyListeners('testSucceeded', [$result->setSucceeded($t, $timer->elapsedTime())]);
      }
    }
  }

  /**
   * Runs a test group
   *
   * @param  unittest.TestGroup $group
   * @param  unittest.TestResult $result
   * @return void
   */
  protected function runGroup($group, $result) {
    try {
      foreach ($group->before() as $name => $before) {
        $this->invoke($before, null);
      }
    } catch (PrerequisitesNotMetError $p) {
      return $this->skipAll($result, $group->targets(), $p);
    } catch (Throwable $t) {
      return $this->skipAll($result, $group->targets(), new PrerequisitesNotMetError(
        'Exception in beforeClass method '.$name,
        $t
      ));
    }

    foreach ($group->targets() as $target) {
      $this->runTarget($target, $result);
    }

    foreach ($group->after() as $after) {
      try {
        $this->invoke($after, null);
      } catch (Throwable $ignored) { }
    }
  }

  /**
   * Run a single test
   *
   * @param   unittest.TestCase test
   * @return  unittest.TestResult
   * @throws  lang.IllegalArgumentException in case given argument is not a testcase
   * @throws  lang.MethodNotImplementedException in case given argument is not a valid testcase
   */
  public function runTest(TestCase $test) {
    $this->notifyListeners('testRunStarted', [$this]);

    $result= new TestResult();
    $this->runGroup(new TestInstance($test), $result);

    $this->notifyListeners('testRunFinished', [$this, $result]);
    return $result;
  }
  
  /**
   * Run this test suite
   *
   * @return  unittest.TestResult
   */
  public function run() {
    $this->notifyListeners('testRunStarted', [$this]);

    $result= new TestResult();
    foreach ($this->groups as $group) {     
      $this->runGroup($group, $result);
    }

    $this->notifyListeners('testRunFinished', [$this, $result]);
    return $result;
  }

  /**
   * Creates a string representation of this test suite
   *
   * @return  string
   */
  public function toString() {
    $s= nameof($this)."@{\n";
    foreach ($this->groups as $group) {
      $s.= '  '.$group->toString()."\n";
    }
    return $s.'}';
  }
}
