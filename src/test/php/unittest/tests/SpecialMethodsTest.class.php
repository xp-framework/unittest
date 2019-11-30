<?php namespace unittest\tests;

use lang\IllegalStateException;
use unittest\{TestCase, TestSuite};

/**
 * Test TestCase class special methods cannot be overwritten as test methods
 *
 * @see      xp://unittest.TestSuite
 */
class SpecialMethodsTest extends TestCase {
  protected $suite= null;
    
  /**
   * Setup method. Creates a new test suite.
   */
  public function setUp() {
    $this->suite= new TestSuite();
  }
  
  /**
   * Returns a testcase with setUp() as test method
   *
   * @return  unittest.TestCase
   */
  protected function setUpCase() {
    return newinstance(TestCase::class, ['setUp'], '{
      #[@test]
      public function setUp() { }
    }');
  }

  #[@test]
  public function stateUnchanged() {
    $test= newinstance(TestCase::class, ['irrelevant'], '{
      #[@test]
      public function irrelevant() { }

      #[@test]
      public function tearDown() { }
    }');
    
    try {
      $this->suite->addTestClass(typeof($test));
      $this->fail('Expected exception not caught', null, IllegalStateException::class);
    } catch (IllegalStateException $expected) {
      $this->assertEquals(0, $this->suite->numTests(), 'Number of test may not have changed');
    }
  }
  
  #[@test, @expect(['class' => IllegalStateException::class, 'withMessage' => '/Cannot override/'])]
  public function setUpMethodMayNotBeATestInAddTestClass() {
    $this->suite->addTestClass(typeof($this->setUpCase()));
  }

  #[@test, @expect(['class' => IllegalStateException::class, 'withMessage' => '/Cannot override/'])]
  public function setUpMethodMayNotBeATestInAddTest() {
    $this->suite->addTest($this->setUpCase());
  }

  /**
   * Returns a testcase with tearDown() as test method
   *
   * @return  unittest.TestCase
   */
  protected function tearDownCase() {
    return newinstance(TestCase::class, ['tearDown'], '{
      #[@test]
      public function tearDown() { }
    }');
  }

  /**
   * Returns a testcase with getName() as test method
   *
   * @return  unittest.TestCase
   */
  protected function getNameCase() {
    return newinstance(TestCase::class, ['getName'], '{
      #[@test]
      public function getName($compound= FALSE) { }
    }');
  }

  #[@test, @expect(['class' => IllegalStateException::class, 'withMessage' => '/Cannot override/'])]
  public function tearDownMethodMayNotBeATestInAddTestClass() {
    $this->suite->addTestClass(typeof($this->tearDownCase()));
  }

  #[@test, @expect(['class' => IllegalStateException::class, 'withMessage' => '/Cannot override/'])]
  public function tearDownMethodMayNotBeATestInAddTest() {
    $this->suite->addTest($this->tearDownCase());
  }

  #[@test, @expect(['class' => IllegalStateException::class, 'withMessage' => '/Cannot override/'])]
  public function getNameMethodMayNotBeATestInAddTestClass() {
    $this->suite->addTestClass(typeof($this->getNameCase()));
  }

  #[@test, @expect(['class' => IllegalStateException::class, 'withMessage' => '/Cannot override/'])]
  public function getNameMethodMayNotBeATestInAddTest() {
    $this->suite->addTest($this->getNameCase());
  }
}