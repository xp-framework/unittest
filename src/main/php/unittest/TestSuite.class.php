<?php namespace unittest;

use util\profiling\Timer;
use util\NoSuchElementException;
use lang\MethodNotImplementedException;
use lang\IllegalStateException;
use lang\IllegalArgumentException;
use lang\XPClass;
use lang\Throwable;
use lang\Error;
use lang\mirrors\TargetInvocationException;
use lang\mirrors\TypeMirror;
use lang\mirrors\InstanceMirror;

/**
 * Test suite
 *
 * @test   xp://net.xp_framework.unittest.tests.SuiteTest
 * @test   xp://net.xp_framework.unittest.tests.ListenerTest
 * @test   xp://net.xp_framework.unittest.tests.BeforeAndAfterClassTest
 * @see    http://junit.sourceforge.net/doc/testinfected/testing.htm
 */
class TestSuite extends \lang\Object {
  protected $listeners= [];
  private $sources= [];
  private $numTests= 0;

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
   * @return  php.Generator
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
   * @param  lang.types.TypeMirror self
   * @return var values a traversable structure
   */
  protected function valuesFor($test, $annotation, $self) {
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
      return $self->method($source)->invoke($test, $args);
    }

    $ref= substr($source, 0, $p);
    $mirror= 'self' === $ref ? $self : new TypeMirror($ref);
    return $mirror->method(substr($source, $p + 2))->invoke(null, $args);
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
    $annotations= $annotatable->annotations();
    if ($annotations->provides('action')) {
      $action= $annotations->named('action')->value();
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
   * @param   lang.mirrors.TypeMirror mirror
   * @return  void
   * @throws  lang.MethodNotImplementedException
   */
  protected function runInternal($test, $result, $mirror) {
    $method= $mirror->method($test->name);
    $annotations= $method->annotations(); 
    $this->notifyListeners('testStarted', [$test]);
    
    // Check for @ignore
    if ($annotations->provides('ignore')) {
      $this->notifyListeners('testNotRun', [
        $result->set($test, new TestNotRun($test, new IgnoredBecause($annotations->named('ignore')->value())))
      ]);
      return;
    }

    // Check for @expect
    $expected= null;
    if ($annotations->provides('expect')) {
      $expect= $annotations->named('expect')->value();
      if (!isset($expect['withMessage'])) {
        $expected= [XPClass::forName($expect), null]; 
      } else if ('/' === $expect['withMessage']{0}) {
        $expected= [XPClass::forName($expect['class']), $expect['withMessage']]; 
      } else {
        $expected= [XPClass::forName($expect['class']), '/'.preg_quote($expect['withMessage'], '/').'/']; 
      }
    }
    
    // Check for @limit
    $eta= 0;
    if ($annotations->provides('limit')) {
      $eta= $annotations->named('limit')->value()['time'];
    }

    // Check for @values
    if ($annotations->provides('values')) {
      $annotation= $annotations->named('values')->value();
      $variation= true;
      $values= $this->valuesFor($test, $annotation, $mirror);
    } else {
      $variation= false;
      $values= [[]];
    }

    // Check for @actions
    $actions= array_merge(
      $this->actionsFor($mirror, 'unittest.TestAction'),
      $this->actionsFor($method, 'unittest.TestAction')
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

      if ($e) {
        if ($expected && $expected[0]->isInstance($e)) {
          if ($eta && $timer->elapsedTime() > $eta) {
            $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
              'Test runtime of %.3f seconds longer than eta of %.3f seconds',
              [$timer->elapsedTime(), $eta]
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
        } else if ($e instanceof IgnoredBecause) {
          $report('testSkipped', TestNotRun::class, $e);
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
      } else if ($eta && $timer->elapsedTime() > $eta) {
        $report('testFailed', TestAssertionFailed::class, new AssertionFailedError(new FormattedMessage(
          'Test runtime of %.3f seconds longer than eta of %.3f seconds',
          [$timer->elapsedTime(), $eta]
        )));
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
   * @param   string method
   * @param   var[] args
   * @return  void
   */
  protected function notifyListeners($method, $args) {
    foreach ($this->listeners as $l) {
      call_user_func_array([$l, $method], $args);
    }
  }

  /**
   * Call beforeClass methods if present. If any of them throws an exception,
   * mark all tests in this class as skipped and continue with tests from
   * other classes (if available)
   *
   * @param  lang.mirrors.TypeMirror mirror
   * @return void
   */
  protected function beforeClass($mirror) {
    foreach ($mirror->methods() as $method) {
      if (!$method->annotations()->provides('beforeClass')) continue;
      try {
        $method->invoke(null, []);
      } catch (TargetInvocationException $e) {
        $cause= $e->getCause();
        if ($cause instanceof PrerequisitesNotMetError) {
          throw $cause;
        } else {
          throw new PrerequisitesNotMetError('Exception in beforeClass method '.$method->name(), $cause);
        }
      }
    }

    $type= $mirror->type();
    foreach ($this->actionsFor($mirror, 'unittest.TestClassAction') as $action) {
      $action->beforeTestClass($type);
    }
  }
  
  /**
   * Call afterClass methods of the last test's class. Ignore any 
   * exceptions thrown from these methods.
   *
   * @param  lang.mirrors.TypeMirror mirror
   * @return void
   */
  protected function afterClass($mirror) {
    $type= $mirror->type();
    foreach ($this->actionsFor($mirror, 'unittest.TestClassAction') as $action) {
      $action->afterTestClass($type);
    }

    foreach ($mirror->methods() as $method) {
      if (!$method->annotations()->provides('afterClass')) continue;
      try {
        $method->invoke(null, []);
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
    $mirror= new InstanceMirror($test);
    if (!$mirror->methods()->provides($test->name)) {
      throw new MethodNotImplementedException('Test method does not exist', $test->name);
    }
    $this->notifyListeners('testRunStarted', [$this]);

    // Run the single test
    $result= new TestResult();
    try {
      $this->beforeClass($mirror);
      $this->runInternal($test, $result, $mirror);
      $this->afterClass($mirror);
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
        $mirror= new TypeMirror($classname);

        // Run all tests in this class
        try {
          $this->beforeClass($mirror);
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
            $this->runInternal($test, $result, $mirror);
          }
        }
        $this->afterClass($mirror);
      }
      $this->notifyListeners('testRunFinished', [$this, $result, null]);
    } catch (StopTests $stop) {
      $this->notifyListeners('testRunFinished', [$this, $result, $stop]);
    }

    return $result;
  }
  
  /**
   * Creates a string representation of this test suite
   *
   * @return  string
   */
  public function toString() {
    $s= nameof($this).'['.sizeof($this->tests)."]@{\n";
    foreach ($this->tests as $test) {
      $s.= '  '.$test->toString()."\n";
    }
    return $s.'}';
  }
}
