<?php namespace unittest\tests;

use lang\Reflection;
use unittest\{
  AfterClass,
  BeforeClass,
  PrerequisitesFailedError,
  PrerequisitesNotMetError,
  Test,
  TestCase,
  TestFailure,
  TestSkipped,
  TestSuite
};

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

  #[Test]
  public function beforeClassMethodIsExecuted() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $initialized= false;

      #[BeforeClass]
      public static function prepareTestData() {
        self::$initialized= true;
      }

      #[Test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(true, Reflection::of($t)->property('initialized')->get(null));
  }

  #[Test]
  public function exceptionInBeforeClassSkipsTest() {
    $t= newinstance(TestCase::class, ['fixture'], '{

      #[BeforeClass]
      public static function prepareTestData() {
        throw new \lang\IllegalStateException("Test data not available");
      }

      #[Test]
      public function fixture() { 
        $this->fail("Will not be run");
      }
    }');
    $r= $this->suite->runTest($t)->outComeOf($t);
    $this->assertInstanceOf(TestSkipped::class, $r);
    $this->assertInstanceOf(PrerequisitesNotMetError::class, $r->reason);
    $this->assertEquals('Exception in beforeClass method prepareTestData', $r->reason->getMessage());
  }

  #[Test]
  public function unmetPrerequisiteInBeforeClassSkipsTest() {
    $t= newinstance(TestCase::class, ['fixture'], '{

      #[BeforeClass]
      public static function prepareTestData() {
        throw new \unittest\PrerequisitesNotMetError("Test data not available", null, ["data"]);
      }

      #[Test]
      public function fixture() { 
        $this->fail("Will not be run");
      }
    }');
    $r= $this->suite->runTest($t)->outComeOf($t);
    $this->assertInstanceOf(TestSkipped::class, $r);
    $this->assertInstanceOf(PrerequisitesNotMetError::class, $r->reason);
    $this->assertEquals('Test data not available', $r->reason->getMessage());
  }

  #[Test]
  public function failedPrerequisiteInBeforeClassFailsTest() {
    $t= newinstance(TestCase::class, ['fixture'], '{

      #[BeforeClass]
      public static function prepareTestData() {
        throw new \unittest\PrerequisitesFailedError("Test data not available", null, ["data"]);
      }

      #[Test]
      public function fixture() {
        $this->fail("Will not be run");
      }
    }');
    $r= $this->suite->runTest($t)->outComeOf($t);
    $this->assertInstanceOf(TestFailure::class, $r);
    $this->assertInstanceOf(PrerequisitesFailedError::class, $r->reason);
    $this->assertEquals('Test data not available', $r->reason->getMessage());
  }

  #[Test]
  public function afterClassMethodIsExecuted() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $finalized= FALSE;

      #[AfterClass]
      public static function deleteTestData() {
        self::$finalized= TRUE;
      }

      #[Test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(true, Reflection::of($t)->property('finalized')->get(null));
  }

  #[Test]
  public function allBeforeClassMethodsAreExecuted() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $initialized= [];

      #[BeforeClass]
      public static function prepareTestData() {
        self::$initialized[]= "data";
      }

      #[BeforeClass]
      public static function connectToDatabase() {
        self::$initialized[]= "conn";
      }

      #[Test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(['data', 'conn'], Reflection::of($t)->property('initialized')->get(null));
  }

  #[Test]
  public function allAfterClassMethodsAreExecuted() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $finalized= [];

      #[BeforeClass]
      public static function disconnectFromDatabase() {
        self::$finalized[]= "conn";
      }

      #[BeforeClass]
      public static function deleteTestData() {
        self::$finalized[]= "data";
      }

      #[Test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(['conn', 'data'], Reflection::of($t)->property('finalized')->get(null));
  }

  #[Test]
  public function afterClassMethodIsNotExecutedWhenPrerequisitesFail() {
    $t= newinstance(TestCase::class, ['fixture'], '{
      public static $finalized= FALSE;

      #[BeforeClass]
      public static function prepareTestData() {
        throw new \unittest\PrerequisitesNotMetError("Test data not available", null, ["data"]);
      }

      #[AfterClass]
      public static function deleteTestData() {
        self::$finalized= TRUE;
      }

      #[Test]
      public function fixture() { }
    }');
    $this->suite->runTest($t);
    $this->assertEquals(false, Reflection::of($t)->property('finalized')->get(null));
  }
}