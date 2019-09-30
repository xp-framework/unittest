<?php namespace unittest\tests;

use unittest\{PrerequisitesFailedError, PrerequisitesNotMetError, TestCase, TestFailure, TestSkipped, TestSuite};

/**
 * Tests @beforeClass and @afterClass methods
 *
 * @see   xp://unittest.TestSuite
 */
abstract class BeforeAndAfterClassTest extends TestCase {
  protected $suite= null;
    
  /**
   * Setup method. Creates a new test suite.
   */
  public function setUp() {
    $this->suite= new TestSuite();
  }

  /**
   * Runs a test and returns the outcome
   *
   * @param   unittest.TestCase test
   * @return  unittest.TestOutcome
   */
  protected abstract function runTest($test);

  #[@test]
  public function beforeClassMethodIsExecuted() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $initialized= false;

      #[@beforeClass]
      public static function prepareTestData() {
        self::$initialized= true;
      }

      #[@test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(true, typeof($t)->getField('initialized')->get(null));
  }

  #[@test]
  public function exceptionInBeforeClassSkipsTest() {
    $t= newinstance(TestCase::class, ['fixture'], '{

      #[@beforeClass]
      public static function prepareTestData() {
        throw new \lang\IllegalStateException("Test data not available");
      }

      #[@test]
      public function fixture() { 
        $this->fail("Will not be run");
      }
    }');
    $r= $this->suite->runTest($t)->outComeOf($t);
    $this->assertInstanceOf(TestSkipped::class, $r);
    $this->assertInstanceOf(PrerequisitesNotMetError::class, $r->reason);
    $this->assertEquals('Exception in beforeClass method prepareTestData', $r->reason->getMessage());
  }

  #[@test]
  public function unmetPrerequisiteInBeforeClassSkipsTest() {
    $t= newinstance(TestCase::class, ['fixture'], '{

      #[@beforeClass]
      public static function prepareTestData() {
        throw new \unittest\PrerequisitesNotMetError("Test data not available", null, ["data"]);
      }

      #[@test]
      public function fixture() { 
        $this->fail("Will not be run");
      }
    }');
    $r= $this->suite->runTest($t)->outComeOf($t);
    $this->assertInstanceOf(TestSkipped::class, $r);
    $this->assertInstanceOf(PrerequisitesNotMetError::class, $r->reason);
    $this->assertEquals('Test data not available', $r->reason->getMessage());
  }

  #[@test]
  public function failedPrerequisiteInBeforeClassFailsTest() {
    $t= newinstance(TestCase::class, ['fixture'], '{

      #[@beforeClass]
      public static function prepareTestData() {
        throw new \unittest\PrerequisitesFailedError("Test data not available", null, ["data"]);
      }

      #[@test]
      public function fixture() {
        $this->fail("Will not be run");
      }
    }');
    $r= $this->suite->runTest($t)->outComeOf($t);
    $this->assertInstanceOf(TestFailure::class, $r);
    $this->assertInstanceOf(PrerequisitesFailedError::class, $r->reason);
    $this->assertEquals('Test data not available', $r->reason->getMessage());
  }

  #[@test]
  public function afterClassMethodIsExecuted() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $finalized= FALSE;

      #[@afterClass]
      public static function deleteTestData() {
        self::$finalized= TRUE;
      }

      #[@test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(true, typeof($t)->getField('finalized')->get(null));
  }

  #[@test]
  public function allBeforeClassMethodsAreExecuted() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $initialized= [];

      #[@beforeClass]
      public static function prepareTestData() {
        self::$initialized[]= "data";
      }

      #[@beforeClass]
      public static function connectToDatabase() {
        self::$initialized[]= "conn";
      }

      #[@test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(['data', 'conn'], typeof($t)->getField('initialized')->get(null));
  }

  #[@test]
  public function allAfterClassMethodsAreExecuted() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $finalized= [];

      #[@beforeClass]
      public static function disconnectFromDatabase() {
        self::$finalized[]= "conn";
      }

      #[@beforeClass]
      public static function deleteTestData() {
        self::$finalized[]= "data";
      }

      #[@test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(['conn', 'data'], typeof($t)->getField('finalized')->get(null));
  }

  #[@test]
  public function afterClassMethodIsNotExecutedWhenPrerequisitesFail() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $finalized= FALSE;

      #[@beforeClass]
      public static function prepareTestData() {
        throw new \unittest\PrerequisitesNotMetError("Test data not available", null, ["data"]);
      }

      #[@afterClass]
      public static function deleteTestData() {
        self::$finalized= TRUE;
      }

      #[@test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(false, typeof($t)->getField('finalized')->get(null));
  }
}