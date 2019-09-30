<?php namespace unittest\tests;

use unittest\{TestCase, TestSuite};

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

  #[@test]
  public function timeouts() {
    $r= $this->suite->runTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @limit(time= 0.010)] fixture' => function() {
        usleep(20 * 1000);
      }
    ]));
    $this->assertEquals(1, $r->failureCount());
  }    

  #[@test]
  public function noTimeout() {
    $r= $this->suite->runTest(newinstance(TestCase::class, ['fixture'], [
      '#[@test, @limit(time= 0.010)] fixture' => function() {
        /* No timeout */
      }
    ]));
    $this->assertEquals(1, $r->successCount());
  }    
}