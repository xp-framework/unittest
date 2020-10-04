<?php namespace unittest\tests;

use lang\{ClassLoader, IllegalStateException, XPClass};
use unittest\{Action, PrerequisitesNotMetError, Test, TestCase, TestPrerequisitesNotMet, TestSuite};

/**
 * Test test actions
 */
class TestActionTest extends TestCase {
  protected $suite, $parent;

  /** @return void */
  public function setUp() {
    $this->suite= new TestSuite();
    $this->parent= class_exists(\lang\Object::class) ? 'lang.Object' : null;  // XP9
  }

  #[Test]
  public function beforeTest_and_afterTest_invocation_order() {
    $test= newinstance(TestCase::class, ['fixture'], [
      'run' => [],
      '#[Test, Action(eval: "new \\\\unittest\\\\tests\\\\RecordActionInvocation(\"run\")")] fixture' => function() {
        $this->run[]= 'test';
      }
    ]);
    $this->suite->runTest($test);
    $this->assertEquals(['before', 'test', 'after'], $test->run);
  }

  #[Test]
  public function beforeTest_is_invoked_before_setUp() {
    $test= newinstance(TestCase::class, ['fixture'], [
      'run' => [],
      'setUp' => function() {
        $this->run[]= 'setup';
      },
      '#[Test, Action(eval: "new \\\\unittest\\\\tests\\\\RecordActionInvocation(\"run\")")] fixture' => function() {
        $this->run[]= 'test';
      }
    ]);
    $this->suite->runTest($test);
    $this->assertEquals(['before', 'setup', 'test', 'after'], $test->run);
  }

  #[Test]
  public function afterTest_is_invoked_after_tearDown() {
    $test= newinstance(TestCase::class, ['fixture'], [
      'run' => [],
      'tearDown' => function() {
        $this->run[]= 'teardown';
      },
      '#[Test, Action(eval: "new \\\\unittest\\\\tests\\\\RecordActionInvocation(\"run\")")] fixture' => function() {
        $this->run[]= 'test';
      }
    ]);
    $this->suite->runTest($test);
    $this->assertEquals(['before', 'test', 'teardown', 'after'], $test->run);
  }

  #[Test]
  public function beforeTest_can_skip_test() {
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test, Action(eval: "new \\\\unittest\\\\tests\\\\SkipThis()")] fixture' => function() {
        throw new IllegalStateException('This test should have been skipped');
      }
    ]);
    $r= $this->suite->runTest($test);
    $this->assertEquals(1, $r->skipCount());
  }

  #[Test]
  public function afterTest_is_invoked_for_succeeding_actions() {
    $actions= [];
    ClassLoader::defineClass('unittest.tests.AllocateMemory', $this->parent, ['unittest.TestAction'], [
      'beforeTest' => function(Test $t) use(&$actions) { $actions[]= 'allocated'; },
      'afterTest'  => function(Test $t) use(&$actions) { $actions[]= 'freed'; }
    ]);
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test, Action(eval: "[new \\\\unittest\\\\tests\\\\AllocateMemory(), new \\\\unittest\\\\tests\\\\SkipThis()]")] fixture' => function() {
        throw new IllegalStateException('This test should have been skipped');
      }
    ]);
    $r= $this->suite->runTest($test);
    $this->assertEquals([1, ['allocated', 'freed']], [$r->skipCount(), $actions]);
  }

  #[Test]
  public function invocation_order_with_class_annotation() {
    $this->suite->addTestClass(XPClass::forName('unittest.tests.TestWithAction'));

    $r= $this->suite->run();
    $result= [];
    foreach ($r->succeeded as $outcome) {
      $result= array_merge($result, $outcome->test->instance->run);
    }

    $this->assertEquals(['before', 'one', 'after', 'before', 'two', 'after'], $result );
  }

  #[Test]
  public function test_action_with_arguments() {
    ClassLoader::defineClass('unittest.tests.PlatformVerification', $this->parent, ['unittest.TestAction'], '{
      protected $platform;

      public function __construct($platform) {
        $this->platform= $platform;
      }

      public function beforeTest(\unittest\Test $t) {
        if (PHP_OS !== $this->platform) {
          throw new \unittest\PrerequisitesNotMetError("Skip", NULL, $this->platform);
        }
      }

      public function afterTest(\unittest\Test $t) {
        // NOOP
      }
    }');
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test, Action(eval: "new \\\\unittest\\\\tests\\\\PlatformVerification(\"Test\")")] fixture' => function() {
        throw new IllegalStateException('This test should have been skipped');
      }
    ]);
    $outcome= $this->suite->runTest($test)->outcomeOf($test);
    $this->assertInstanceOf(TestPrerequisitesNotMet::class, $outcome);
    $this->assertEquals(['Test'], $outcome->reason->prerequisites);
  }

  #[Test]
  public function skip_test_via_skip() {
    ClassLoader::defineClass('unittest.tests.SkipTest', $this->parent, ['unittest.TestAction'], [
      'beforeTest' => function(Test $t) { $t->instance->skip('Not run'); },
      'afterTest'  => function(Test $t) { }
    ]);
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test, Action(eval: "[new \\\\unittest\\\\tests\\\\SkipTest()]")] fixture' => function() {
        throw new IllegalStateException('This test should have been skipped');
      }
    ]);
    $r= $this->suite->runTest($test);
    $this->assertEquals(1, $r->skipCount());
  }

  #[Test]
  public function multiple_actions() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $one= [], $two= [];

      #[Test, Action(eval: "[new \\\\unittest\\\\tests\\\\RecordActionInvocation(\"one\"), new \\\\unittest\\\\tests\\\\RecordActionInvocation(\"two\")]")]
      public function fixture() {
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals(
      ['one' => ['before', 'after'], 'two' => ['before', 'after']],
      ['one' =>  $test->one, 'two' => $test->two]
    );
  }

  #[Test]
  public function afterTest_can_raise_AssertionFailedErrors() {
    ClassLoader::defineClass('unittest.tests.FailOnTearDown', $this->parent, ['unittest.TestAction'], '{
      public function beforeTest(\unittest\Test $t) {
        // NOOP
      }

      public function afterTest(\unittest\Test $t) {
        throw new \unittest\AssertionFailedError("Skip");
      }
    }');
    $test= newinstance(TestCase::class, ['fixture'], [
      '#[Test, Action(eval: "new \\\\unittest\\\\tests\\\\FailOnTearDown()")] fixture' => function() {
        // NOOP
      }
    ]);
    $r= $this->suite->runTest($test);
    $this->assertEquals(1, $r->failureCount());
  }

  #[Test]
  public function all_afterTest_exceptions_are_chained_into_one() {
    ClassLoader::defineClass('unittest.tests.FailOnTearDownWith', $this->parent, ['unittest.TestAction'], '{
      protected $message;

      public function __construct($message) {
        $this->message= $message;
      }

      public function beforeTest(\unittest\Test $t) {
        // NOOP
      }

      public function afterTest(\unittest\Test $t) {
        throw new \unittest\AssertionFailedError($this->message);
      }
    }');
    $test= newinstance(TestCase::class, ['fixture'], '{
      #[Test, Action(eval: "[new \\\\unittest\\\\tests\\\\FailOnTearDownWith(\"First\"), new \\\\unittest\\\\tests\\\\FailOnTearDownWith(\"Second\")]")]
      public function fixture() {
        // NOOP
      }
    }');
    $r= $this->suite->runTest($test);
    $outcome= $r->outcomeOf($test);
    $this->assertEquals(['Second', 'First'], [$outcome->reason->getMessage(), $outcome->reason->getCause()->getMessage()]);
  }
}