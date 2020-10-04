<?php namespace unittest\tests;

use unittest\{Test, TestCase, TestSuite};

/**
 * Test TestSuite class methods
 *
 * @see    xp://unittest.TestSuite
 */
class LimitTest extends TestCase {
  private $suite;
    
  /** @return void */
  public function setUp() {
    $this->suite= new TestSuite();
  }

  #[Test]
  public function timeouts() {
    $r= $this->suite->runTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Limit(time: 0.010)] fixture' => function() {
        usleep(20 * 1000);
      }
    ]));
    $this->assertEquals(1, $r->failureCount());
  }    

  #[Test]
  public function noTimeout() {
    $r= $this->suite->runTest(newinstance(TestCase::class, ['fixture'], [
      '#[Test, Limit(time: 0.010)] fixture' => function() {
        /* No timeout */
      }
    ]));
    $this->assertEquals(1, $r->successCount());
  }    
}