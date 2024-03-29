<?php namespace unittest\tests;
 
use unittest\{Expect, Test, TestCase, TestSuite, Values};

/**
 * Test values annotation
 *
 * @see  xp://unittest.TestSuite
 * @see  https://github.com/xp-framework/xp-framework/issues/313
 * @see  https://github.com/xp-framework/xp-framework/issues/298
 */
class ValuesTest extends TestCase {
  private $suite;
    
  /**
   * Setup method. Creates a new test suite.
   */
  public function setUp() {
    $this->suite= new TestSuite();
  }

  /**
   * Values for external_value_source tests
   *
   * @param  int lo
   * @param  int hi
   * @return var[]
   */
  public static function range($lo= 1, $hi= 3) {
    return range($lo, $hi);
  }

  #[Test]
  public function inline_value_source() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      #[Test, Values([1, 2, 3])]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function inline_value_map() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      #[Test, Values(["map" => ["a" => "b", "c" => "d"]])]
      public function fixture($key, $value) {
        $this->values[]= [$key, $value];
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([['a', 'b'], ['c', 'd']], $test->values);
  }

  #[Test]
  public function local_value_source() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      public function values() {
        return [1, 2, 3];
      }

      #[Test, Values("values")]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function local_value_source_with_args() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      public function range($lo= 1, $hi= 3) {
        return range($lo, $hi);
      }

      #[Test, Values(["source" => "range", "args" => [1, 4]])]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3, 4], $test->values);
  }

  #[Test]
  public function local_value_source_without_args() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      public function range($lo= 1, $hi= 3) {
        return range($lo, $hi);
      }

      #[Test, Values(["source" => "range"])]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function external_value_source_fully_qualified_class() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      #[Test, Values("unittest.tests.ValuesTest::range")]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function external_value_source_unqualified_class() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      #[Test, Values("unittest\\\\tests\\\\ValuesTest::range")]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function external_value_source_provider_and_args() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      #[Test, Values(["source" => "unittest.tests.ValuesTest::range", "args" => [1, 10]])]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $test->values);
  }

  #[Test]
  public function local_value_source_with_self() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      public static function range() {
        return [1, 2, 3];
      }

      #[Test, Values("self::range")]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function all_variants_succeed() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      #[Test, Values([1, 2, 3])]
      public function fixture($value) {
        $this->assertTrue(true);
      }
    }');
    $r= $this->suite->runTest($test);
    $this->assertEquals(3, $r->successCount());
  }

  #[Test]
  public function all_variants_fail() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      #[Test, Values([1, 2, 3])]
      public function fixture($value) {
        $this->assertTrue(false);
      }
    }');
    $r= $this->suite->runTest($test);
    $this->assertEquals(3, $r->failureCount());
  }

  #[Test]
  public function all_variants_skipped() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public function setUp() {
        throw new PrerequisitesNotMetError("Not ready yet");
      }

      #[Test, Values([1, 2, 3])]
      public function fixture($value) {
        throw new Error("Will never be reached");
      }
    }');
    $r= $this->suite->runTest($test);
    $this->assertEquals(3, $r->skipCount());
  }

  #[Test]
  public function some_variants_succeed_some_fail() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      #[Test, Values([1, 2, 3])]
      public function fixture($value) {
        $this->assertEquals(0, $value % 2);
      }
    }');
    $r= $this->suite->runTest($test);
    $this->assertEquals(1, $r->successCount());
    $this->assertEquals(2, $r->failureCount());
  }

  #[Test]
  public function supplying_values_for_multiple_parameters() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      #[Test, Values([[1, 2], [3, 4], [5, 6]])]
      public function fixture($a, $b) {
        $this->values[]= $a;
        $this->values[]= $b;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3, 4, 5, 6], $test->values);
  }

  #[Test]
  public function using_traversable_in_values() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      public function values() {
        return new \ArrayObject([1, 2, 3]);
      }

      #[Test, Values("values")]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function using_this_in_value_provider() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      public function values() {
        return [$this];
      }

      #[Test, Values("values")]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([$test], $test->values);
  }

  #[Test]
  public function protected_local_values_method() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      protected function values() {
        return [1, 2, 3];
      }

      #[Test, Values("values")]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function private_local_values_method() {
    $test= newinstance(TestCase::class, ['fixture'], '{
      public $values= [];

      private function values() {
        return [1, 2, 3];
      }

      #[Test, Values("values")]
      public function fixture($value) {
        $this->values[]= $value;
      }
    }');
    $this->suite->runTest($test);
    $this->assertEquals([1, 2, 3], $test->values);
  }

  #[Test]
  public function values_with_expect() {
    $test= newinstance(TestCase::class, ['not_at_number'], '{
      #[Test, Values(["a"]), Expect("lang.FormatException")]
      public function not_at_number($value) {
        throw new \lang\FormatException("Not a number: ".$value);
      }
    }');
    $r= $this->suite->runTest($test);
    $this->assertEquals(1, $r->successCount());
  }
}