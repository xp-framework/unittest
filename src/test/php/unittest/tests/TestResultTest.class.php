<?php namespace unittest\tests;
 
use unittest\metrics\Metric;
use unittest\{AssertionFailedError, PrerequisitesNotMetError, Test, TestCase, TestCaseInstance, TestError, TestResult, TestSkipped, TestSuccess, Values};

class TestResultTest extends TestCase {
  private $test;

  /** @return void */
  public function setUp() {
    $this->test= new TestCaseInstance($this);
  }

  #[Test]
  public function can_create() {
    new TestResult();
  }

  #[Test]
  public function record_success() {
    $outcome= new TestSuccess($this->test, 0.0);
    $this->assertEquals($outcome, (new TestResult())->record($outcome));
  }

  #[Test]
  public function record_skipped() {
    $outcome= new TestSkipped($this->test, 0.0);
    $this->assertEquals($outcome, (new TestResult())->record($outcome));
  }

  #[Test]
  public function record_failure() {
    $outcome= new TestError($this->test, new AssertionFailedError('Fail!'), 0.0);
    $this->assertEquals($outcome, (new TestResult())->record($outcome));
  }

  #[Test]
  public function outcome_of_recorded_test() {
    $outcome= new TestSuccess($this->test, 0.0);
    $t= new TestResult();
    $t->record($outcome);
    $this->assertEquals($outcome, $t->outcomeOf($this->test));
  }

  #[Test]
  public function outcome_of_non_existant_test() {
    $t= new TestResult();
    $this->assertNull($t->outcomeOf($this->test));
  }

  #[Test]
  public function initial_counts_are_zero() {
    $t= new TestResult();
    $this->assertEquals(
      [0, 0, 0, 0, 0],
      [$t->successCount(), $t->skipCount(), $t->failureCount(), $t->runCount(), $t->count()]
    );
  }

  #[Test]
  public function one_succeeded_test() {
    $t= new TestResult();
    $t->record(new TestSuccess($this->test, 0.0));
    $this->assertEquals(
      [1, 0, 0, 1, 1],
      [$t->successCount(), $t->skipCount(), $t->failureCount(), $t->runCount(), $t->count()]
    );
  }

  #[Test]
  public function succeed_skipped_and_failed_tests() {
    $t= new TestResult();
    $t->record(new TestSuccess($this->test, 0.0));
    $t->record(new TestSkipped($this->test, 0.0));
    $t->record(new TestError($this->test, new AssertionFailedError('Fail!'), 0.0));
    $this->assertEquals(
      [1, 1, 1, 2, 3],
      [$t->successCount(), $t->skipCount(), $t->failureCount(), $t->runCount(), $t->count()]
    );
  }

  #[Test]
  public function elapsed() {
    $t= new TestResult();
    $t->record(new TestSuccess($this->test, 1.0));
    $t->record(new TestSkipped($this->test, 0.1));
    $t->record(new TestError($this->test, new AssertionFailedError('Fail!'), 0.5));
    $this->assertEquals(1.6, $t->elapsed());
  }

  #[Test]
  public function string_representation() {
    $t= new TestResult();
    $t->record(new TestSuccess($this->test, 0.0));
    $t->record(new TestSkipped($this->test, 0.0));
    $t->record(new TestError($this->test, new AssertionFailedError('Fail!'), 0.0));
    $this->assertNotEquals('', $t->toString());
  }

  #[Test]
  public function hash_code() {
    $this->assertNotEquals('', (new TestResult())->hashCode());
  }

  #[Test]
  public function equals_itself() {
    $this->assertEquals(new TestResult(), new TestResult());
  }

  #[Test]
  public function does_not_equal_other() {
    $this->assertNotEquals($this, new TestResult());
  }

  #[Test, Values(['Memory used', 'Time taken'])]
  public function default_metric($name) {
    $metrics= (new TestResult())->metrics();
    $this->assertTrue(isset($metrics[$name]));
  }

  #[Test]
  public function record_metric() {
    $metric= newinstance(Metric::class, [], [
      'calculate' => function() {  },
      'value'     => function() { return 6100; },
      'format'    => function() { return 'Test'; }
    ]);
    $this->assertEquals($metric, (new TestResult())->metric('Test', $metric)->metrics()['Test']);
  }
}