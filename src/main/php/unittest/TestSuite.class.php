<?php namespace unittest;

use util\profiling\Timer;
use util\NoSuchElementException;
use lang\MethodNotImplementedException;
use lang\IllegalStateException;
use lang\IllegalArgumentException;
use lang\XPClass;
use lang\Throwable;
use lang\mirrors\TargetInvocationException;
use lang\mirrors\TypeMirror;

/**
 * Test suite
 *
 * @test   xp://net.xp_framework.unittest.tests.SuiteTest
 * @test   xp://net.xp_framework.unittest.tests.ListenerTest
 * @test   xp://net.xp_framework.unittest.tests.BeforeAndAfterClassTest
 * @see    http://junit.sourceforge.net/doc/testinfected/testing.htm
 */
class TestSuite extends \lang\Object {
  public $tests= [];
  protected $order= [];
  protected $listeners= [];

  private static $BASE, $TESTS, $BEFORECLASS, $AFTERCLASS;

  static function __static() {
    self::$BASE= new TypeMirror('unittest.TestCase');
    self::$TESTS= newinstance('util.Filter', [], '{
      public function accept($m) { return $m->annotations()->provides("test"); }
    }');
    self::$BEFORECLASS= newinstance('util.Filter', [], '{
      public function accept($m) { return $m->annotations()->provides("beforeClass"); }
    }');
    self::$AFTERCLASS= newinstance('util.Filter', [], '{
      public function accept($m) { return $m->annotations()->provides("afterClass"); }
    }');
  }

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
    $mirror= new TypeMirror(typeof($test));
    if (!$mirror->methods()->provides($test->name)) {
      throw new MethodNotImplementedException('Test method does not exist', $test->name);
    }
    $className= $mirror->name();
    
    // Verify no special method, e.g. setUp() or tearDown() is overwritten.
    if (self::$BASE->methods()->provides($test->name)) {
      throw new IllegalStateException(sprintf(
        'Cannot override %s::%s with test method in %s',
        self::$BASE->name(),
        $test->name,
        $mirror->method($test->name)->declaredIn()->name()
      ));
    }
    
    if (!isset($this->order[$className])) $this->order[$className]= [];
    $this->order[$className][]= sizeof($this->tests);
    $this->tests[]= $test;
    return $test;
  }

  /**
   * Add a test class
   *
   * @param   lang.XPClass<unittest.TestCase> class
   * @param   var[] arguments default [] arguments to pass to test case constructor
   * @return  lang.XPClass<unittest.TestCase> class
   * @throws  lang.IllegalArgumentException in case given argument is not a testcase class
   * @throws  util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function addTestClass($class, $arguments= []) {
    $mirror= new TypeMirror($class);
    if (!$mirror->isSubtypeOf(self::$BASE)) {
      throw new IllegalArgumentException('Given argument is not a TestCase class ('.\xp::stringOf($class).')');
    }

    $ignored= [];
    $numBefore= $this->numTests();
    $className= $mirror->name();
    $tests= $this->tests;
    $order= $this->order;
    if (!isset($this->order[$className])) $this->order[$className]= [];

    foreach ($mirror->methods()->all(self::$TESTS) as $m) {
      
      // Verify no special method, e.g. setUp() or tearDown() is overwritten.
      if (self::$BASE->methods()->provides($m->name())) {
        $this->tests= $tests;
        $this->order= $order;
        throw new IllegalStateException(sprintf(
          'Cannot override %s::%s with test method in %s',
          self::$BASE->name(),
          $m->name(),
          $m->declaredIn()->name()
        ));
      }

      $this->tests[]= $class->getConstructor()->newInstance(array_merge(
        (array)$m->name(),
        $arguments
      ));
      $this->order[$className][]= sizeof($this->tests)- 1;
    }

    if ($numBefore === $this->numTests()) {
      if (empty($this->order[$className])) unset($this->order[$className]);
      throw new NoSuchElementException('No tests found in '.$mirror->name());
    }

    return $class;
  }
  
  /**
   * Returns number of tests in this suite
   *
   * @return  int
   */
  public function numTests() {
    return sizeof($this->tests);
  }
  
  /**
   * Remove all tests
   *
   */
  public function clearTests() {
    $this->tests= [];
    $this->order= [];
  }
  
  /**
   * Returns test at a given position
   *
   * @param   int pos
   * @return  unittest.TestCase or NULL if none was found
   */
  public function testAt($pos) {
    if (isset($this->tests[$pos])) return $this->tests[$pos]; else return null;
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
      return (new TypeMirror(typeof($test)))->method($source)->invoke($test, $args);
    } else {
      $ref= substr($source, 0, $p);
      $mirror= new TypeMirror('self' === $ref ? typeof($test) : $ref);
      return $mirror->method(substr($source, $p+ 2))->invoke(null, $args);
    }
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
   * Run a test case.
   *
   * @param   unittest.TestCase test
   * @param   unittest.TestResult result
   * @throws  lang.MethodNotImplementedException
   */
  protected function runInternal($test, $result) {
    $mirror= new TypeMirror(typeof($test));
    $method= $mirror->method($test->name);
    $annotations= $method->annotations();
    $this->notifyListeners('testStarted', [$test]);
    
    // Check for @ignore
    if ($annotations->provides('ignore')) {
      $this->notifyListeners('testNotRun', [
        $result->set($test, new TestNotRun($test, $annotations->named('ignore')->value()))
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
      $variation= true;
      $values= $this->valuesFor($test, $annotations->named('values')->value());
    } else {
      $variation= false;
      $values= [[]];
    }

    // Check for @actions, initialize setUp and tearDown call chains
    $actions= array_merge(
      $this->actionsFor($mirror, 'unittest.TestAction'),
      $this->actionsFor($method, 'unittest.TestAction')
    );
    $setUp= function($test) use($actions) {
      foreach ($actions as $action) {
        $action->beforeTest($test);
      }
      $test->setUp();
    };
    $tearDown= function($test) use($actions) {
      $test->tearDown();
      $raised= null;
      foreach ($actions as $action) {
        try {
          $action->afterTest($test);
        } catch (Throwable $e) {
          $e->setCause($raised);
          $raised= $e;
        }
      }
      if ($raised) throw $raised;
    };

    $timer= new Timer();
    foreach ($values as $args) {
      $t= $variation ? new TestVariation($test, $args) : $test;
      \xp::gc();
      $timer->start();

      // Setup test
      try {
        $setUp($test);
      } catch (PrerequisitesNotMetError $e) {
        $timer->stop();
        $this->notifyListeners('testSkipped', [
          $result->setSkipped($t, $e, $timer->elapsedTime())
        ]);
        \xp::gc();
        continue;
      } catch (AssertionFailedError $e) {
        $timer->stop();
        $this->notifyListeners('testFailed', [
          $result->setFailed($t, $e, $timer->elapsedTime())
        ]);
        \xp::gc();
        continue;
      } catch (Throwable $x) {
        $timer->stop();
        $this->notifyListeners('testFailed', [
          $result->set($t, new TestError($t, $x, $timer->elapsedTime()))
        ]);
        \xp::gc();
        continue;
      }

      // Run test
      $e= null;
      try {
        $method->invoke($test, is_array($args) ? $args : [$args]);
        $tearDown($test);
      } catch (TargetInvocationException $x) {
        $tearDown($test);
        $e= $x->getCause();
      } catch (Throwable $e) {
        // Exception inside teardown
      }
      $timer->stop();

      if ($e) {
        if ($expected && $expected[0]->isInstance($e)) {
          if ($eta && $timer->elapsedTime() > $eta) {
            $this->notifyListeners('testFailed', [
              $result->setFailed(
                $t,
                new AssertionFailedError(new FormattedMessage(
                  'Test runtime of %.3f seconds longer than eta of %.3f seconds',
                  [$timer->elapsedTime(), $eta]
                )),
                $timer->elapsedTime()
              )
            ]);
          } else if ($expected[1] && !preg_match($expected[1], $e->getMessage())) {
            $this->notifyListeners('testFailed', [
              $result->setFailed(
                $t,
                new AssertionFailedError(new FormattedMessage(
                  'Expected %s\'s message "%s" differs from expected %s',
                  [nameof($e), $e->getMessage(), $expected[1]]
                )),
                $timer->elapsedTime()
              )
            ]);
          } else if (sizeof(\xp::$errors) > 0) {
            $this->notifyListeners('testWarning', [
              $result->set($t, new TestWarning($t, $this->formatErrors(\xp::$errors), $timer->elapsedTime()))
            ]);
          } else {
            $this->notifyListeners('testSucceeded', [
              $result->setSucceeded($t, $timer->elapsedTime())
            ]);
          }
        } else if ($expected && !$expected[0]->isInstance($e)) {
          $this->notifyListeners('testFailed', [
            $result->setFailed(
              $t,
              new AssertionFailedError(new FormattedMessage(
                'Caught %s instead of expected %s',
                [$e->compoundMessage(), $expected[0]->getName()]
              )),
              $timer->elapsedTime()
            )
          ]);
        } else if ($e instanceof AssertionFailedError) {
          $this->notifyListeners('testFailed', [
            $result->setFailed($t, $e, $timer->elapsedTime())
          ]);
        } else if ($e instanceof PrerequisitesNotMetError) {
          $this->notifyListeners('testSkipped', [
            $result->setSkipped($t, $e, $timer->elapsedTime())
          ]);
        } else {
          $this->notifyListeners('testError', [
            $result->set($t, new TestError($t, $e, $timer->elapsedTime()))
          ]);
        }
        \xp::gc();
        continue;
      } else if ($expected) {
        $this->notifyListeners('testFailed', [
          $result->setFailed(
            $t,
            new AssertionFailedError(new FormattedMessage('Expected %s not caught', [$expected[0]->getName()])),
            $timer->elapsedTime()
          )
        ]);
      } else if (sizeof(\xp::$errors) > 0) {
        $this->notifyListeners('testWarning', [
          $result->set($t, new TestWarning($t, $this->formatErrors(\xp::$errors), $timer->elapsedTime()))
        ]);
      } else if ($eta && $timer->elapsedTime() > $eta) {
        $this->notifyListeners('testFailed', [
          $result->setFailed(
            $t,
            new AssertionFailedError('Timeout', sprintf('%.3f', $timer->elapsedTime()), sprintf('%.3f', $eta)), 
            $timer->elapsedTime()
          )
        ]);
      } else {
        $this->notifyListeners('testSucceeded', [
          $result->setSucceeded($t, $timer->elapsedTime())
        ]);
      }
      \xp::gc();
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
   * @param  lang.XPClass class
   */
  protected function beforeClass($class) {
    $mirror= new TypeMirror($class);
    foreach ($mirror->methods()->all(self::$BEFORECLASS) as $m) {
      if (!$m->annotations()->provides('beforeClass')) continue;
      try {
        $m->invoke(null, []);
      } catch (TargetInvocationException $e) {
        $cause= $e->getCause();
        if ($cause instanceof PrerequisitesNotMetError) {
          throw $cause;
        } else {
          throw new PrerequisitesNotMetError('Exception in beforeClass method '.$m->name(), $cause);
        }
      }
    }
    foreach ($this->actionsFor($mirror, 'unittest.TestClassAction') as $action) {
      $action->beforeTestClass($class);
    }
  }
  
  /**
   * Call afterClass methods of the last test's class. Ignore any 
   * exceptions thrown from these methods.
   *
   * @param  lang.XPClass class
   */
  protected function afterClass($class) {
    $mirror= new TypeMirror($class);
    foreach ($this->actionsFor($mirror, 'unittest.TestClassAction') as $action) {
      $action->afterTestClass($class);
    }
    foreach ($mirror->methods()->all(self::$AFTERCLASS) as $m) {
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
    $class= $test->getClass();
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
    } catch (PrerequisitesNotMetError $e) {
      $this->notifyListeners('testSkipped', [$result->setSkipped($test, $e, 0.0)]);
    }

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
    foreach ($this->order as $classname => $tests) {
      $class= XPClass::forName($classname);

      // Run all tests in this class
      try {
        $this->beforeClass($class);
      } catch (PrerequisitesNotMetError $e) {
        foreach ($tests as $i) {
          $this->notifyListeners('testSkipped', [$result->setSkipped($this->tests[$i], $e, 0.0)]);
        }
        continue;
      }
      foreach ($tests as $i) {
        $this->runInternal($this->tests[$i], $result);
      }
      $this->afterClass($class);
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
    $s= nameof($this).'['.sizeof($this->tests)."]@{\n";
    foreach ($this->tests as $test) {
      $s.= '  '.$test->toString()."\n";
    }
    return $s.'}';
  }
}
