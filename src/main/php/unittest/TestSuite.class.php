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
use util\Objects;

/**
 * Test suite
 *
 * @test   xp://net.xp_framework.unittest.tests.SuiteTest
 * @test   xp://net.xp_framework.unittest.tests.ListenerTest
 * @test   xp://net.xp_framework.unittest.tests.BeforeAndAfterClassTest
 * @see    http://junit.sourceforge.net/doc/testinfected/testing.htm
 */
class TestSuite implements \lang\Value {
  protected $listeners= [];
  private $sources= [];

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
    $this->sources[get_class($test)][]= new TestInstance($test);
    return $test;
  }

  /**
   * Add a test class
   *
   * @param   lang.XPClass<unittest.TestCase> class
   * @param   var[] arguments default [] arguments to pass to test case constructor
   * @return  lang.XPClass
   * @throws  lang.IllegalArgumentException in case given argument is not a testcase class
   * @throws  util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function addTestClass($class, $arguments= []) {
    $this->sources[$class->literal()][]= new TestClass($class, $arguments);
    return $class;
  }

  /**
   * Returns number of tests in this suite
   *
   * @return  int
   */
  public function numTests() {
    $numTests= 0;
    foreach ($this->sources as $classname => $groups) {
      foreach ($groups as $group) {
        $numTests+= $group->numTests();
      }
    }
    return $numTests;
  }
  
  /**
   * Remove all tests
   *
   * @return void
   */
  public function clearTests() {
    $this->sources= [];
  }
  
  /**
   * Returns test at a given position
   *
   * @param   int pos
   * @return  unittest.TestCase or NULL if none was found
   */
  public function testAt($pos) {
    $num= 0;
    foreach ($this->sources as $classname => $groups) {
      foreach ($groups as $group) {
        foreach ($group->tests() as $test) {
          if ($num++ === $pos) return $test;
        }
      }
    }
    return null;
  }

  /**
   * Returns all tests
   *
   * @return  iterable
   */
  public function tests() {
    foreach ($this->sources as $classname => $groups) {
      foreach ($groups as $group) {
        foreach ($group->tests() as $test) {
          yield $test;
        }
      }
    }
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
   * Run a test case.
   *
   * @param   unittest.TestCase test
   * @param   unittest.TestResult result
   * @return  void
   * @throws  lang.MethodNotImplementedException
   */
  protected function runInternal($test, $result) {
    $class= typeof($test);
    $method= $class->getMethod($test->name);
    $this->notifyListeners('testStarted', [$test]);
    
    // Check for @ignore
    if ($method->hasAnnotation('ignore')) {
      $this->notifyListeners('testNotRun', [
        $result->set($test, new TestNotRun($test, new IgnoredBecause($method->getAnnotation('ignore'))))
      ]);
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
    $report= function($type, $outcome, $arg) use($result, $timer, &$t) {
      $timer->stop();
      $this->notifyListeners($type, [$result->set($t, new $outcome($t, $arg, $timer->elapsedTime()))]);
      \xp::gc();
    };
    \xp::gc();
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
        $report($aborted->type(), $aborted->outcome(), $aborted);
        continue;
      } catch (TargetInvocationException $invoke) {
        $thrown= $tearDown($test, $invoke->getCause());
      } catch (Throwable $error) {
        $thrown= $tearDown($test, $error);
      }

      $timer->stop();

      // Check outcome
      if ($eta && $timer->elapsedTime() > $eta) {
        $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
          'Test runtime of %.3f seconds longer than eta of %.3f seconds',
          [$timer->elapsedTime(), $eta]
        )));
      } else if ($thrown) {
        if ($expected && $expected[0]->isInstance($thrown)) {
          if ($expected[1] && !preg_match($expected[1], $thrown->getMessage())) {
            $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
              'Expected %s\'s message "%s" differs from expected %s',
              [nameof($thrown), $thrown->getMessage(), $expected[1]]
            )));
          } else if (sizeof(\xp::$errors) > 0) {
            $report('testWarning', TestWarning::class, $this->formatErrors(\xp::$errors));
          } else {
            $this->notifyListeners('testSucceeded', [$result->setSucceeded($t, $timer->elapsedTime())]);
          }
        } else if ($expected && !$expected[0]->isInstance($thrown)) {
          $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
            'Caught %s instead of expected %s',
            [$thrown->compoundMessage(), $expected[0]->getName()]
          )));
        } else if ($thrown instanceof TestAborted) {
          $report($thrown->type(), $thrown->outcome(), $thrown);
        } else {
          $report('testError', TestError::class, $thrown);
        }
      } else if ($expected) {
        $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
          'Expected %s not caught',
          [$expected[0]->getName()]
        )));
      } else if (sizeof(\xp::$errors) > 0) {
        $report('testWarning', TestWarning::class, $this->formatErrors(\xp::$errors));
      } else {
        $this->notifyListeners('testSucceeded', [$result->setSucceeded($t, $timer->elapsedTime())]);
      }
    }
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
   * Run a single test
   *
   * @param   unittest.TestCase test
   * @return  unittest.TestResult
   * @throws  lang.IllegalArgumentException in case given argument is not a testcase
   * @throws  lang.MethodNotImplementedException in case given argument is not a valid testcase
   */
  public function runTest(TestCase $test) {
    $class= typeof($test);
    if (!$class->hasMethod($test->name)) {
      throw new MethodNotImplementedException('Test method does not exist', $test->name);
    }
    $this->notifyListeners('testRunStarted', [$this]);

    // Run the single test
    $result= new TestResult();
    try {
      $this->beforeClass($class);
      $this->runInternal($test, $result);
      $this->afterClass($class);
      $this->notifyListeners('testRunFinished', [$this, $result, null]);
    } catch (PrerequisitesNotMetError $e) {
      $this->notifyListeners('testSkipped', [$result->setSkipped($test, $e, 0.0)]);
    } catch (StopTests $stop) {
      $this->notifyListeners('testRunFinished', [$this, $result, $stop]);
    }

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
    try {
      foreach ($this->sources as $classname => $groups) {
        $class= new XPClass($classname);

        // Run all tests in this class
        try {
          $this->beforeClass($class);
        } catch (PrerequisitesNotMetError $e) {
          foreach ($groups as $group) {
            foreach ($group->tests() as $test) {
              $this->notifyListeners('testSkipped', [$result->setSkipped($test, $e, 0.0)]);
            }
          }
          continue;
        }

        foreach ($groups as $group) {
          foreach ($group->tests() as $test) {
            $this->runInternal($test, $result);
          }
        }
        $this->afterClass($class);
      }
      $this->notifyListeners('testRunFinished', [$this, $result, null]);
    } catch (StopTests $stop) {
      $this->notifyListeners('testRunFinished', [$this, $result, $stop]);
    }

    return $result;
  }

  /** @return string */
  public function toString() {
    $s= nameof($this).'['.sizeof($this->sources)."]@{\n";
    foreach ($this->sources as $source) {
      $s.= '  '.$source->toString()."\n";
    }
    return $s.'}';
  }

  /** @return string */
  public function hashCode() {
    return 'S'.Objects::hashOf($this->sources);
  }

  /**
   * Compares this test suite to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->sources, $value->sources) : 1;
  }
}
