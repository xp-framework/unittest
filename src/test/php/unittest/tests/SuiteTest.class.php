<?php namespace unittest\tests;

use unittest\TestCase;
use unittest\TestResult;
use unittest\TestPrerequisitesNotMet;
use lang\Error;
use lang\Object;
use lang\MethodNotImplementedException;
use util\NoSuchElementException;
use unittest\TestSuite;
use unittest\actions\RuntimeVersion;
use unittest\PrerequisitesNotMetError;
use unittest\AssertionFailedError;
use lang\IllegalArgumentException;
use lang\FormatException;
use lang\ClassLoader;

/**
 * Test TestSuite class methods
 *
 * @see    xp://unittest.TestSuite
 */
class SuiteTest extends TestCase {
  private $suite;
    
  /** @return void */
  public function setUp() {
    $this->suite= new TestSuite();
  }

  /**
   * Defines a class with a `beforeClass`-annotated method which sets a member
   * variable `before` to true when run.
   *
   * @param  string $name
   * @return lang.XPClass
   */
  private function classWithBeforeClass($name) {
    return ClassLoader::defineClass($name, TestCase::class, [], '{
      public static $before= false;

      #[@beforeClass]
      public static function before() {
        self::$before= true;
      }

      #[@test]
      public function fixture() { /* Empty */ }
    }');
  }

  /**
   * Defines a class with a `afterClass`-annotated method which sets a member
   * variable `after` to true when run.
   *
   * @param  string $name
   * @return lang.XPClass
   */
  private function classWithAfterClass($name) {
    return ClassLoader::defineClass($name, TestCase::class, [], '{
      public static $after= false;

      #[@afterClass]
      public static function after() {
        self::$after= true;
      }

      #[@test]
      public function fixture() { /* Empty */ }
    }');
  }

  #[@test]
  public function initallyEmpty() {
    $this->assertEquals(0, $this->suite->numTests());
  }    

  #[@test]
  public function addingATest() {
    $this->suite->addTest($this);
    $this->assertEquals(1, $this->suite->numTests());
  }    

  #[@test]
  public function addingATestTwice() {
    $this->suite->addTest($this);
    $this->suite->addTest($this);
    $this->assertEquals(2, $this->suite->numTests());
  }    

  #[@test, @expect(IllegalArgumentException::class), @action(new RuntimeVersion('<7.0.0-dev'))]
  public function addNonTest() {
    $this->suite->addTest(new Object());
  }

  #[@test, @expect(Error::class), @action(new RuntimeVersion('>=7.0.0-dev'))]
  public function addNonTest7() {
    $this->suite->addTest(new Object());
  }

  #[@test, @expect(IllegalArgumentException::class), @action(new RuntimeVersion('<7.0.0-dev'))]
  public function runNonTest() {
    $this->suite->runTest(new Object());
  }

  #[@test, @expect(Error::class), @action(new RuntimeVersion('>=7.0.0-dev'))]
  public function runNonTest7() {
    $this->suite->runTest(new Object());
  }

  #[@test, @expect(MethodNotImplementedException::class)]
  public function addInvalidTest() {
    $this->suite->addTest(newinstance(TestCase::class, ['nonExistant'], '{}'));
  }

  #[@test, @expect(MethodNotImplementedException::class)]
  public function runInvalidTest() {
    $this->suite->runTest(newinstance(TestCase::class, ['nonExistant'], '{}'));
  }

  #[@test]
  public function adding_a_testclass_returns_added_class() {
    $class= ClassLoader::defineClass($this->name, 'unittest.TestCase', [], '{
      #[@test]
      public function fixture() { }
    }');
    $this->assertEquals($class, $this->suite->addTestClass($class));
  }

  #[@test]
  public function adding_a_testclass_fills_suites_tests() {
    $class= ClassLoader::defineClass($this->name, 'unittest.TestCase', [], [
      '#[@test] a' => function() { },
      '#[@test] b' => function() { }
    ]);
    $this->suite->addTestClass($class);
    $this->assertEquals(2, $this->suite->numTests());
    $this->assertInstanceOf(TestCase::class, $this->suite->testAt(0));
    $this->assertInstanceOf(TestCase::class, $this->suite->testAt(1));
  }

  #[@test]
  public function adding_a_testclass_twice_fills_suites_tests_twice() {
    $class= ClassLoader::defineClass($this->name, 'unittest.TestCase', [], [
      '#[@test] fixture' => function() { }
    ]);
    $this->suite->addTestClass($class);
    $this->suite->addTestClass($class);
    $this->assertEquals(2, $this->suite->numTests());
  }

  #[@test, @expect(NoSuchElementException::class)]
  public function addingEmptyTest() {
    $this->suite->addTestClass(ClassLoader::defineClass($this->name, 'unittest.TestCase', []));
  }    

  #[@test]
  public function addingEmptyTestAfter() {
    $this->suite->addTestClass(ClassLoader::defineClass($this->name.'WithTest', 'unittest.TestCase', [], [
      '#[@test] fixture' => function() { }
    ]));
    $before= $this->suite->numTests();
    try {
      $this->suite->addTestClass(ClassLoader::defineClass($this->name.'Empty', 'unittest.TestCase', []));
      $this->fail('Expected exception not thrown', null, 'util.NoSuchElementException');
    } catch (\util\NoSuchElementException $expected) { 
    }
    $this->assertEquals($before, $this->suite->numTests());
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function addingANonTestClass() {
    $this->suite->addTestClass(\lang\XPClass::forName('lang.Object'));
  }    

  #[@test]
  public function clearingTests() {
    $this->suite->addTest($this);
    $this->assertEquals(1, $this->suite->numTests());
    $this->suite->clearTests();
    $this->assertEquals(0, $this->suite->numTests());
  }

  #[@test]
  public function tests_initially_empty() {
    $this->assertEquals([], iterator_to_array($this->suite->tests()));
  }

  #[@test]
  public function tests_after_adding_one() {
    $this->suite->addTest($this);
    $this->assertEquals([$this], iterator_to_array($this->suite->tests()));
  }

  #[@test]
  public function tests_after_adding_two() {
    $this->suite->addTest($this);
    $this->suite->addTest($this);
    $this->assertEquals([$this, $this], iterator_to_array($this->suite->tests()));
  }

  #[@test]
  public function runningASingleSucceedingTest() {
    $r= $this->suite->runTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(1, $r->count(), 'count');
    $this->assertEquals(1, $r->runCount(), 'runCount');
    $this->assertEquals(1, $r->successCount(), 'successCount');
    $this->assertEquals(0, $r->failureCount(), 'failureCount');
    $this->assertEquals(0, $r->skipCount(), 'skipCount');
  }    

  #[@test]
  public function runningASingleFailingTest() {
    $r= $this->suite->runTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(1, $r->count(), 'count');
    $this->assertEquals(1, $r->runCount(), 'runCount');
    $this->assertEquals(0, $r->successCount(), 'successCount');
    $this->assertEquals(1, $r->failureCount(), 'failureCount');
    $this->assertEquals(0, $r->skipCount(), 'skipCount');
  }    

  #[@test]
  public function runMultipleTests() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new PrerequisitesNotMetError('Skip'); },
      '#[@test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @ignore] fixture' => function() { /* Empty */ }
    ]));
    $r= $this->suite->run();
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(4, $r->count(), 'count');
    $this->assertEquals(2, $r->runCount(), 'runCount');
    $this->assertEquals(1, $r->successCount(), 'successCount');
    $this->assertEquals(1, $r->failureCount(), 'failureCount');
    $this->assertEquals(2, $r->skipCount(), 'skipCount');
  }    

  #[@test]
  public function runInvokesBeforeClassOneClass() {
    $class= $this->classWithBeforeClass($this->name);
    $this->suite->addTest($class->newInstance('fixture'));
    $this->suite->run();
    $this->assertTrue($class->getField('before')->get(null));
  }

  #[@test]
  public function runInvokesBeforeClassMultipleClasses() {
    $class= $this->classWithBeforeClass($this->name);
    $this->suite->addTest($class->newInstance('fixture'));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { /* Empty */ }
    ]));
    $this->suite->addTest($class->newInstance('fixture'));
    $this->suite->run();
    $this->assertTrue($class->getField('before')->get(null));
  }

  #[@test]
  public function runTestInvokesBeforeClass() {
    $class= $this->classWithBeforeClass($this->name);
    $this->suite->runTest($class->newInstance('fixture'));
    $this->assertTrue($class->getField('before')->get(null));
  }    

  #[@test]
  public function beforeClassRaisesAPrerequisitesNotMet() {
    $t= newinstance(TestCase::class, ['irrelevant'], '{
      #[@beforeClass]
      public static function raise() {
        throw new \unittest\PrerequisitesNotMetError("Cannot run");
      }
      
      #[@test]
      public function irrelevant() {
        /* Not invoked */
      }
    }');
    $this->suite->addTest($t);
    $r= $this->suite->run();
    $this->assertEquals(1, $r->skipCount(), 'skipCount');
    $this->assertInstanceOf(TestPrerequisitesNotMet::class, $r->outcomeOf($t));
    $this->assertInstanceOf(PrerequisitesNotMetError::class, $r->outcomeOf($t)->reason);
    $this->assertEquals('Cannot run', $r->outcomeOf($t)->reason->getMessage());
  }    

  #[@test]
  public function beforeClassRaisesAnException() {
    $t= newinstance(TestCase::class, ['irrelevant'], '{
      #[@beforeClass]
      public static function raise() {
        throw new \lang\IllegalStateException("Skip");
      }
      
      #[@test]
      public function irrelevant() {
        /* Not invoked */
      }
    }');
    $this->suite->addTest($t);
    $r= $this->suite->run();
    $this->assertEquals(1, $r->skipCount(), 'skipCount');
    $this->assertInstanceOf(TestPrerequisitesNotMet::class, $r->outcomeOf($t));
    $this->assertInstanceOf(PrerequisitesNotMetError::class, $r->outcomeOf($t)->reason);
    $this->assertEquals('Exception in beforeClass method raise', $r->outcomeOf($t)->reason->getMessage());
  }    

  #[@test]
  public function runInvokesAfterClass() {
    $class= $this->classWithAfterClass($this->name);
    $this->suite->addTest($class->newInstance('fixture'));
    $this->suite->run();
    $this->assertTrue($class->getField('after')->get(null));
  }    

  #[@test]
  public function runTestInvokesAfterClass() {
    $class= $this->classWithAfterClass($this->name);
    $this->suite->runTest($class->newInstance('fixture'));
    $this->assertTrue($class->getField('after')->get(null));
  }    

  #[@test]
  public function warningsMakeTestFail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { trigger_error('Test error'); }
    ]);
    $this->assertEquals(
      [sprintf('"Test error" in ::trigger_error() (SuiteTest.class.php, line %d, occured once)', __LINE__ - 3)],
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason
    );
  }

  #[@test]
  public function xp_exceptions_make_test_fail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { throw new IllegalArgumentException('Test'); }
    ]);
    $this->assertInstanceOf(
      'lang.IllegalArgumentException',
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason
    );
  }

  #[@test]
  public function native_exceptions_make_test_fail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { throw new \Exception('Test'); }
    ]);
    $this->assertInstanceOf(
      'lang.XPException',
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason
    );
  }

  #[@test, @action(new RuntimeVersion('>=7.0.0'))]
  public function native_php7_errors_make_test_fail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $null= null; $null->method(); }
    ]);
    $this->assertInstanceOf(
      'lang.Error',
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason
    );
  }

  #[@test]
  public function expectedExceptionsWithWarningsMakeTestFail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[@test, @expect("lang.IllegalArgumentException")] fixture' => function() {
        trigger_error('Test error');
        throw new IllegalArgumentException('Test');
      }
    ]);
    $this->assertEquals(
      [sprintf('"Test error" in ::trigger_error() (SuiteTest.class.php, line %d, occured once)', __LINE__ - 5)],
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason
    );
  }
  
  #[@test]
  public function warningsDontAffectSucceedingTests() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { trigger_error('Test error'); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
    $this->assertEquals(1, $r->successCount());
  }
 
  #[@test]
  public function warningsFromFailuresDontAffectSucceedingTests() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { trigger_error('Test error'); $this->assertTrue(false); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
    $this->assertEquals(1, $r->successCount());
  }

  #[@test]
  public function warningsFromSetupDontAffectSucceedingTests() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { trigger_error('Error'); },
      '#[@test] fixture' => function() { /* Empty */ }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[@test]
  public function expectedException() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @expect("lang.IllegalArgumentException")] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[@test]
  public function subclassOfExpectedException() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @expect("lang.XPException")] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[@test]
  public function expectedExceptionNotThrown() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @expect("lang.IllegalArgumentException")] fixture' => function() {
        throw new FormatException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
    $this->assertEquals(
      'Caught Exception lang.FormatException (Test) instead of expected lang.IllegalArgumentException', 
      cast($r->outcomeOf($this->suite->testAt(0)), 'unittest.TestFailure')->reason->getMessage()
    );
  }

  #[@test]
  public function catchExpectedWithMessage() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @expect(class= "lang.IllegalArgumentException", withMessage= "Test")] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[@test]
  public function catchExpectedWithMismatchingMessage() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @expect(class= "lang.IllegalArgumentException", withMessage= "Hello")] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
    $this->assertEquals(
      'Expected lang.IllegalArgumentException\'s message "Test" differs from expected /Hello/',
      cast($r->outcomeOf($this->suite->testAt(0)), 'unittest.TestFailure')->reason->getMessage()
    );
  }

  #[@test]
  public function catchExpectedWithPatternMessage() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @expect(class= "lang.IllegalArgumentException", withMessage= "/[tT]est/")] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[@test]
  public function catchExpectedWithEmptyMessage() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @expect(class= "lang.IllegalArgumentException", withMessage= "")] fixture' => function() {
        throw new IllegalArgumentException('');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[@test]
  public function catchExceptionsDuringSetUpOfTestDontBringDownTestSuite() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new IllegalArgumentException('In setup'); },
      'fixture' => function() { /* Intentionally empty */ }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[@test]
  public function fail_with_reason_only() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->fail('Test'); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(
      [1, 'Test'],
      [$r->failureCount(), $r->outcomeOf($this->suite->testAt(0))->reason->getMessage()]
    );
  }

  #[@test]
  public function fail_with_actual_and_expected() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->fail('Not equal', 'a', 'b'); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(
      [1, 'expected ["b"] but was ["a"] using: \'Not equal\''],
      [$r->failureCount(), $r->outcomeOf($this->suite->testAt(0))->reason->getMessage()]
    );
  }

  #[@test]
  public function skip_with_reason() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test] fixture' => function() { $this->skip('Test'); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(
      [1, 'Test'],
      [$r->skipCount(), $r->outcomeOf($this->suite->testAt(0))->reason->getMessage()]
    );
  }

  #[@test]
  public function throwing_PrerequisitesNotMetError_from_setUp_skips_test() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new PrerequisitesNotMetError('Skip'); },
      '#[@test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->skipCount());
  }

  #[@test]
  public function throwing_AssertionFailedError_from_setUp_fails_test() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new AssertionFailedError('Fail'); },
      '#[@test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[@test, @values([IllegalArgumentException::class, \Exception::class])]
  public function throwing_any_other_exception_from_setUp_fails_test($e) {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() use($e) { throw new $e('Fail'); },
      '#[@test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[@test]
  public function throwing_AssertionFailedError_from_tearDown_fails_succeeding_test() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'tearDown' => function() { throw new AssertionFailedError('Fail'); },
      '#[@test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[@test, @values([IllegalArgumentException::class, \Exception::class])]
  public function throwing_any_other_exception_from_tearDown_fails_succeeding_test($e) {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'tearDown' => function() use($e) { throw new $e('Fail'); },
      '#[@test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[@test, @values([IllegalArgumentException::class, \Exception::class])]
  public function throwing_any_other_exception_from_tearDown_fails_failing_test($e) {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'tearDown' => function() use($e) { throw new $e('Fail'); },
      '#[@test] fixture' => function() { $this->fail('Failing'); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }
}
