<?php namespace unittest\tests;
 
use unittest\AssertionFailedError;
use unittest\PrerequisitesNotMetError;
use unittest\TestCase;
use unittest\TestError;
use unittest\TestResult;
use unittest\TestSkipped;
use unittest\TestSuccess;

class TestResultTest extends TestCase {

  #[@test]
  public function can_create() {
    new TestResult();
  }

  #[@test]
  public function record_success() {
    $outcome= new TestSuccess($this, 0.0);
    $this->assertEquals($outcome, (new TestResult())->record($outcome));
  }

  #[@test]
  public function record_skipped() {
    $outcome= new TestSkipped($this, 0.0);
    $this->assertEquals($outcome, (new TestResult())->record($outcome));
  }

  #[@test]
  public function record_failure() {
    $outcome= new TestError($this, new AssertionFailedError('Fail!'), 0.0);
    $this->assertEquals($outcome, (new TestResult())->record($outcome));
  }

  #[@test]
  public function outcome_of_recorded_test() {
    $outcome= new TestSuccess($this, 0.0);
    $t= new TestResult();
    $t->record($outcome);
    $this->assertEquals($outcome, $t->outcomeOf($this));
  }

  #[@test]
  public function outcome_of_non_existant_test() {
    $t= new TestResult();
    $this->assertNull($t->outcomeOf($this));
  }

  #[@test]
  public function initial_counts_are_zero() {
    $t= new TestResult();
    $this->assertEquals(
      [0, 0, 0, 0, 0],
      [$t->successCount(), $t->skipCount(), $t->failureCount(), $t->runCount(), $t->count()]
    );
  }

  #[@test]
  public function one_succeeded_test() {
    $t= new TestResult();
    $t->record(new TestSuccess($this, 0.0));
    $this->assertEquals(
      [1, 0, 0, 1, 1],
      [$t->successCount(), $t->skipCount(), $t->failureCount(), $t->runCount(), $t->count()]
    );
  }

  #[@test]
  public function succeed_skipped_and_failed_tests() {
    $t= new TestResult();
    $t->record(new TestSuccess($this, 0.0));
    $t->record(new TestSkipped($this, 0.0));
    $t->record(new TestError($this, new AssertionFailedError('Fail!'), 0.0));
    $this->assertEquals(
      [1, 1, 1, 2, 3],
      [$t->successCount(), $t->skipCount(), $t->failureCount(), $t->runCount(), $t->count()]
    );
  }

  #[@test]
  public function elapsed() {
    $t= new TestResult();
    $t->record(new TestSuccess($this, 1.0));
    $t->record(new TestSkipped($this, 0.1));
    $t->record(new TestError($this, new AssertionFailedError('Fail!'), 0.5));
    $this->assertEquals(1.6, $t->elapsed());
  }

  #[@test]
  public function string_representation() {
    $t= new TestResult();
    $t->record(new TestSuccess($this, 0.0));
    $t->record(new TestSkipped($this, 0.0));
    $t->record(new TestError($this, new AssertionFailedError('Fail!'), 0.0));
    $this->assertNotEquals('', $t->toString());
  }

  #[@test]
  public function hash_code() {
    $this->assertNotEquals('', (new TestResult())->hashCode());
  }

  #[@test]
  public function equals_itself() {
    $this->assertEquals(new TestResult(), new TestResult());
  }

  #[@test]
  public function does_not_equal_other() {
    $this->assertNotEquals($this, new TestResult());
  }

  #[@test, @values(['Memory used', 'Time taken'])]
  public function default_metric($name) {
    $metrics= (new TestResult())->metrics();
    $this->assertTrue(isset($metrics[$name]));
  }

  #[@test, @values(['Tested', function() { return 'Tested'; }])]
  public function record_metric($metric) {
    $metric= (new TestResult())->metric('Test', $metric)->metrics()['Test'];
    $this->assertEquals('Tested', $metric());
  }

  /** @deprecated */
  #[@test]
  public function set() {
    $outcome= new TestSuccess($this, 0.0);
    $this->assertEquals($outcome, (new TestResult())->set($this, $outcome));
  }

  /** @deprecated */
  #[@test]
  public function setSucceeded() {
    (new TestResult())->setSucceeded($this, 0.0);
  }

  /** @deprecated */
  #[@test]
  public function setSkipped() {
    (new TestResult())->setSkipped($this, new PrerequisitesNotMetError('Ignored'), 0.0);
  }

  /** @deprecated */
  #[@test]
  public function setFailed() {
    (new TestResult())->setFailed($this, new AssertionFailedError('Failed'), 0.0);
  }
}