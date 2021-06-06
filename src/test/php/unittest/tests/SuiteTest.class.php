<?php namespace unittest\tests;

use lang\{ClassLoader, Error, FormatException, IllegalArgumentException, MethodNotImplementedException};
use unittest\actions\RuntimeVersion;
use unittest\{
  Action,
  AfterClass,
  BeforeClass,
  Expect,
  Test,
  Values,
  Assert,
  AssertionFailedError,
  PrerequisitesNotMetError,
  TestCase,
  TestCaseInstance,
  TestPrerequisitesNotMet,
  TestResult,
  TestSuite,
  TestTargets,
  TestMethod
};
use util\NoSuchElementException;

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

      #[BeforeClass]
      public static function before() {
        self::$before= true;
      }

      #[Test]
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

      #[AfterClass]
      public static function after() {
        self::$after= true;
      }

      #[Test]
      public function fixture() { /* Empty */ }
    }');
  }

  #[Test]
  public function initally_empty() {
    $this->assertEquals(0, $this->suite->numTests());
  }    

  #[Test]
  public function add_testcase() {
    $this->suite->addTest($this);
    $this->assertEquals(1, $this->suite->numTests());
  }

  #[Test]
  public function add_testgrup() {
    $test= new class() {

      #[Test]
      public function test() { }
    };
    $this->suite->addTest(new TestMethod(typeof($test), 'test'));
    $this->assertEquals(1, $this->suite->numTests());
  }

  #[Test]
  public function add_testcase_twice() {
    $this->suite->addTest($this);
    $this->suite->addTest($this);
    $this->assertEquals(2, $this->suite->numTests());
  }    

  #[Test, Expect(IllegalArgumentException::class)]
  public function add_non_test() {
    $this->suite->addTest(new NotATestClass());
  }

  #[Test, Expect(MethodNotImplementedException::class)]
  public function add_invalid_Test() {
    $this->suite->addTest(new class('nonExistant') extends TestCase { });
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function runNonTest() {
    $this->suite->runTest(new NotATestClass());
  }

  #[Test, Expect(MethodNotImplementedException::class)]
  public function runInvalidTest() {
    $this->suite->runTest(newinstance(TestCase::class, ['nonExistant'], '{}'));
  }

  #[Test]
  public function adding_a_testclass_returns_added_class() {
    $this->assertEquals(typeof($this), $this->suite->addTestClass(typeof($this)));
  }

  #[Test]
  public function adding_a_testclass_by_name_returns_added_class() {
    $this->assertEquals(typeof($this), $this->suite->addTestClass(self::class));
  }

  #[Test]
  public function number_of_tests_initially_zero() {
    $this->assertEquals(0, $this->suite->numTests());
  }

  #[Test]
  public function no_test_initially() {
    $this->assertNull($this->suite->testAt(0));
  }

  #[Test]
  public function adding_a_testclass_fills_suites_tests() {
    $class= ClassLoader::defineClass($this->name, 'unittest.TestCase', [], [
      '#[Test] a' => function() { },
      '#[Test] b' => function() { }
    ]);
    $this->suite->addTestClass($class);
    $this->assertEquals(2, $this->suite->numTests());
    $this->assertInstanceOf(TestCaseInstance::class, $this->suite->testAt(0));
    $this->assertInstanceOf(TestCaseInstance::class, $this->suite->testAt(1));
  }

  #[Test]
  public function adding_a_testclass_twice_fills_suites_tests_twice() {
    $class= ClassLoader::defineClass($this->name, 'unittest.TestCase', [], [
      '#[Test] fixture' => function() { }
    ]);
    $this->suite->addTestClass($class);
    $this->suite->addTestClass($class);
    $this->assertEquals(2, $this->suite->numTests());
  }

  #[Test, Expect(NoSuchElementException::class)]
  public function addingEmptyTest() {
    $this->suite->addTestClass(ClassLoader::defineClass($this->name, 'unittest.TestCase', []));
  }    

  #[Test]
  public function addingEmptyTestAfter() {
    $this->suite->addTestClass(ClassLoader::defineClass($this->name.'WithTest', 'unittest.TestCase', [], [
      '#[Test] fixture' => function() { }
    ]));
    $before= $this->suite->numTests();
    try {
      $this->suite->addTestClass(ClassLoader::defineClass($this->name.'Empty', 'unittest.TestCase', []));
      $this->fail('Expected exception not thrown', null, 'util.NoSuchElementException');
    } catch (\util\NoSuchElementException $expected) { 
    }
    $this->assertEquals($before, $this->suite->numTests());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function addingANonTestClass() {
    $this->suite->addTestClass(\lang\XPClass::forName('lang.Value'));
  }    

  #[Test]
  public function clearingTests() {
    $this->suite->addTest($this);
    $this->assertEquals(1, $this->suite->numTests());
    $this->suite->clearTests();
    $this->assertEquals(0, $this->suite->numTests());
  }

  #[Test]
  public function tests_initially_empty() {
    $this->assertEquals([], iterator_to_array($this->suite->tests()));
  }

  #[Test]
  public function tests_after_adding_one() {
    $this->suite->addTest($this);
    $this->assertEquals([new TestCaseInstance($this)], iterator_to_array($this->suite->tests()));
  }

  #[Test]
  public function tests_after_adding_two() {
    $this->suite->addTest($this);
    $this->suite->addTest($this);
    $this->assertEquals([new TestCaseInstance($this), new TestCaseInstance($this)], iterator_to_array($this->suite->tests()));
  }

  #[Test]
  public function run_single_succeeding_testcase() {
    $r= $this->suite->runTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(1, $r->count(), 'count');
    $this->assertEquals(1, $r->runCount(), 'runCount');
    $this->assertEquals(1, $r->successCount(), 'successCount');
    $this->assertEquals(0, $r->failureCount(), 'failureCount');
    $this->assertEquals(0, $r->skipCount(), 'skipCount');
  }

  #[Test]
  public function run_single_succeeding_baseless_test() {
    $r= $this->suite->runTest(ClassLoader::defineClass('BaselessSucceeding', Baseless::class, [], [
      '#[Test] fixture' => function() { Assert::true(true); }
    ]));
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(1, $r->count(), 'count');
    $this->assertEquals(1, $r->runCount(), 'runCount');
    $this->assertEquals(1, $r->successCount(), 'successCount');
    $this->assertEquals(0, $r->failureCount(), 'failureCount');
    $this->assertEquals(0, $r->skipCount(), 'skipCount');
  }

  #[Test, Values([[[]], [[1, 2, 3]]])]
  public function run_single_succeeding_targets_with($args) {
    $class= ClassLoader::defineClass('BaselessSucceeding', Baseless::class, [], [
      '#[Test] fixture' => function() { Assert::true(true); }
    ]);
    $r= $this->suite->runTest(new TestTargets($class, $args));
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(1, $r->count(), 'count');
    $this->assertEquals(1, $r->runCount(), 'runCount');
    $this->assertEquals(1, $r->successCount(), 'successCount');
    $this->assertEquals(0, $r->failureCount(), 'failureCount');
    $this->assertEquals(0, $r->skipCount(), 'skipCount');
  }

  #[Test]
  public function run_single_failing_testcase() {
    $r= $this->suite->runTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(1, $r->count(), 'count');
    $this->assertEquals(1, $r->runCount(), 'runCount');
    $this->assertEquals(0, $r->successCount(), 'successCount');
    $this->assertEquals(1, $r->failureCount(), 'failureCount');
    $this->assertEquals(0, $r->skipCount(), 'skipCount');
  }

  #[Test]
  public function run_single_failing_baseless_test() {
    $r= $this->suite->runTest(ClassLoader::defineClass('BaselessFailing', Baseless::class, [], [
      '#[Test] fixture' => function() { Assert::true(false); }
    ]));
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(1, $r->count(), 'count');
    $this->assertEquals(1, $r->runCount(), 'runCount');
    $this->assertEquals(0, $r->successCount(), 'successCount');
    $this->assertEquals(1, $r->failureCount(), 'failureCount');
    $this->assertEquals(0, $r->skipCount(), 'skipCount');
  }

  #[Test]
  public function runMultipleTests() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new PrerequisitesNotMetError('Skip'); },
      '#[Test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Ignore] fixture' => function() { /* Empty */ }
    ]));
    $r= $this->suite->run();
    $this->assertInstanceOf(TestResult::class, $r);
    $this->assertEquals(4, $r->count(), 'count');
    $this->assertEquals(2, $r->runCount(), 'runCount');
    $this->assertEquals(1, $r->successCount(), 'successCount');
    $this->assertEquals(1, $r->failureCount(), 'failureCount');
    $this->assertEquals(2, $r->skipCount(), 'skipCount');
  }    

  #[Test]
  public function runInvokesBeforeClassOneClass() {
    $class= $this->classWithBeforeClass($this->name);
    $this->suite->addTest($class->newInstance('fixture'));
    $this->suite->run();
    $this->assertTrue($class->getField('before')->get(null));
  }

  #[Test]
  public function runInvokesBeforeClassMultipleClasses() {
    $class= $this->classWithBeforeClass($this->name);
    $this->suite->addTest($class->newInstance('fixture'));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { /* Empty */ }
    ]));
    $this->suite->addTest($class->newInstance('fixture'));
    $this->suite->run();
    $this->assertTrue($class->getField('before')->get(null));
  }

  #[Test]
  public function runTestInvokesBeforeClass() {
    $class= $this->classWithBeforeClass($this->name);
    $this->suite->runTest($class->newInstance('fixture'));
    $this->assertTrue($class->getField('before')->get(null));
  }    

  #[Test]
  public function beforeClassRaisesAPrerequisitesNotMet() {
    $t= newinstance(TestCase::class, ['irrelevant'], '{
      #[BeforeClass]
      public static function raise() {
        throw new \unittest\PrerequisitesNotMetError("Cannot run");
      }
      
      #[Test]
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

  #[Test]
  public function beforeClassRaisesAnException() {
    $t= newinstance(TestCase::class, ['irrelevant'], '{
      #[BeforeClass]
      public static function raise() {
        throw new \lang\IllegalStateException("Skip");
      }
      
      #[Test]
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

  #[Test]
  public function runInvokesAfterClass() {
    $class= $this->classWithAfterClass($this->name);
    $this->suite->addTest($class->newInstance('fixture'));
    $this->suite->run();
    $this->assertTrue($class->getField('after')->get(null));
  }    

  #[Test]
  public function runTestInvokesAfterClass() {
    $class= $this->classWithAfterClass($this->name);
    $this->suite->runTest($class->newInstance('fixture'));
    $this->assertTrue($class->getField('after')->get(null));
  }    

  #[Test]
  public function warningsMakeTestFail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { trigger_error('Test error'); }
    ]);

    $l= __LINE__ - 3;
    $this->assertEquals(
      [[__FILE__, $l, sprintf('"Test error" in ::trigger_error() (SuiteTest.class.php, line %d, occured once)', $l)]],
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason->all()
    );
  }

  #[Test]
  public function xp_exceptions_make_test_fail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { throw new IllegalArgumentException('Test'); }
    ]);
    $this->assertInstanceOf(
      'lang.IllegalArgumentException',
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason
    );
  }

  #[Test]
  public function native_exceptions_make_test_fail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { throw new \Exception('Test'); }
    ]);
    $this->assertInstanceOf(
      'lang.XPException',
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason
    );
  }

  #[Test]
  public function native_php7_errors_make_test_fail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $null= null; $null->method(); }
    ]);
    $this->assertInstanceOf(
      'lang.Error',
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason
    );
  }

  #[Test]
  public function expectedExceptionsWithWarningsMakeTestFail() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test, Expect("lang.IllegalArgumentException")] fixture' => function() {
        trigger_error('Test error');
        throw new IllegalArgumentException('Test');
      }
    ]);

    $l= __LINE__ - 5;
    $this->assertEquals(
      [[__FILE__, $l, sprintf('"Test error" in ::trigger_error() (SuiteTest.class.php, line %d, occured once)', $l)]],
      $this->suite->runTest($test)->failed[$test->hashCode()]->reason->all()
    );
  }
  
  #[Test]
  public function warningsDontAffectSucceedingTests() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { trigger_error('Test error'); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
    $this->assertEquals(1, $r->successCount());
  }
 
  #[Test]
  public function warningsFromFailuresDontAffectSucceedingTests() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { trigger_error('Test error'); $this->assertTrue(false); }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
    $this->assertEquals(1, $r->successCount());
  }

  #[Test]
  public function warningsFromSetupDontAffectSucceedingTests() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { trigger_error('Error'); },
      '#[Test] fixture' => function() { /* Empty */ }
    ]));
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[Test]
  public function expectedException() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Expect("lang.IllegalArgumentException")] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[Test]
  public function subclassOfExpectedException() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Expect("lang.XPException")] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[Test]
  public function expectedExceptionNotThrown() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Expect("lang.IllegalArgumentException")] fixture' => function() {
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

  #[Test]
  public function catchExpectedWithMessage() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Expect(["class" => "lang.IllegalArgumentException", "withMessage" => "Test"])] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[Test]
  public function catchExpectedWithMismatchingMessage() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Expect(["class" => "lang.IllegalArgumentException", "withMessage" => "Hello"])] fixture' => function() {
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

  #[Test]
  public function catchExpectedWithPatternMessage() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Expect(["class" => "lang.IllegalArgumentException", "withMessage" => "/[tT]est/"])] fixture' => function() {
        throw new IllegalArgumentException('Test');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[Test]
  public function catchExpectedWithEmptyMessage() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Expect(["class" => "lang.IllegalArgumentException", "withMessage" => ""])] fixture' => function() {
        throw new IllegalArgumentException('');
      }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->successCount());
  }

  #[Test]
  public function catchExceptionsDuringSetUpOfTestDontBringDownTestSuite() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new IllegalArgumentException('In setup'); },
      'fixture' => function() { /* Intentionally empty */ }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[Test]
  public function fail_with_reason_only() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->fail('Test'); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(
      [1, 'Test'],
      [$r->failureCount(), $r->outcomeOf($this->suite->testAt(0))->reason->getMessage()]
    );
  }

  #[Test]
  public function fail_with_actual_and_expected() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->fail('Not equal', 'a', 'b'); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(
      [1, 'expected ["b"] but was ["a"] using: \'Not equal\''],
      [$r->failureCount(), $r->outcomeOf($this->suite->testAt(0))->reason->getMessage()]
    );
  }

  #[Test]
  public function skip_with_reason() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test] fixture' => function() { $this->skip('Test'); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(
      [1, 'Test'],
      [$r->skipCount(), $r->outcomeOf($this->suite->testAt(0))->reason->getMessage()]
    );
  }

  #[Test]
  public function throwing_PrerequisitesNotMetError_from_setUp_skips_test() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new PrerequisitesNotMetError('Skip'); },
      '#[Test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->skipCount());
  }

  #[Test]
  public function throwing_AssertionFailedError_from_setUp_fails_test() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() { throw new AssertionFailedError('Fail'); },
      '#[Test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[Test, Values([IllegalArgumentException::class, \Exception::class])]
  public function throwing_any_other_exception_from_setUp_fails_test($e) {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'setUp' => function() use($e) { throw new $e('Fail'); },
      '#[Test] fixture' => function() { $this->assertTrue(false); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[Test]
  public function throwing_AssertionFailedError_from_tearDown_fails_succeeding_test() {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'tearDown' => function() { throw new AssertionFailedError('Fail'); },
      '#[Test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[Test, Values([IllegalArgumentException::class, \Exception::class])]
  public function throwing_any_other_exception_from_tearDown_fails_succeeding_test($e) {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'tearDown' => function() use($e) { throw new $e('Fail'); },
      '#[Test] fixture' => function() { $this->assertTrue(true); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }

  #[Test, Values([IllegalArgumentException::class, \Exception::class])]
  public function throwing_any_other_exception_from_tearDown_fails_failing_test($e) {
    $this->suite->addTest(newinstance(TestCase::class, ['fixture'], [
      'tearDown' => function() use($e) { throw new $e('Fail'); },
      '#[Test] fixture' => function() { $this->fail('Failing'); }
    ]));
    $r= $this->suite->run();
    $this->assertEquals(1, $r->failureCount());
  }
}