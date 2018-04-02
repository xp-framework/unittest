<?php namespace unittest\tests;
 
use unittest\TestCase;
use unittest\AssertionFailedError;
use util\Objects;

/**
 * Test assertion methods
 */
class AssertionsTest extends TestCase {

  #[@test]
  public function trueIsTrue() {
    $this->assertTrue(true);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function falseIsNotTrue() {
    $this->assertTrue(false);
  }

  #[@test]
  public function falseIsFalse() {
    $this->assertFalse(false);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function trueIsNotFalse() {
    $this->assertFalse(true);
  }

  #[@test]
  public function nullIsNull() {
    $this->assertNull(null);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function falseIsNotNull() {
    $this->assertNull(false);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function zeroIsNotNull() {
    $this->assertNull(0);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function emptyStringIsNotNull() {
    $this->assertNull('');
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function emptyArrayIsNotNull() {
    $this->assertNull([]);
  }

  #[@test]
  public function compareToMethodIsInvoked() {
    $instance= newinstance(\lang\Value::class, [], '{
      public $equalsInvoked= 0;

      public function toString() { return nameof($this)."@".$this->equalsInvoked; }
      public function hashCode() { return "V".$this->equalsInvoked; }
      public function compareTo($other) {
        $this->equalsInvoked++;
        return $other instanceof self ? $this->equalsInvoked - $other->equalsInvoked : 1;
      }
    }');
   
    $this->assertEquals($instance, $instance);
    $this->assertNotEquals($instance, null);
    $this->assertEquals(2, $instance->equalsInvoked);
  }

  #[@test, @values([0, 1, -1, PHP_INT_MAX])]
  public function integersAreEqual($int) {
    $this->assertEquals($int, $int);
  }    

  #[@test, @values(['', 'Hello', 'äöüß'])]
  public function stringsAreEqual($str) {
    $this->assertEquals($str, $str);
  }    

  #[@test, @values([
  #  [[]],
  #  [[1, 2, 3]],
  #  [[[1], [], [-1, 4], [new Value(2)]]]
  #])]
  public function arraysAreEqual($array) {
    $this->assertEquals($array, $array);
  }    

  #[@test, @values([
  #  [[]],
  #  [['foo' => 2]],
  #  [[['bar' => 'baz'], [], ['bool' => true, 'bar' => new Value(6100)]]]
  #])]
  public function hashesAreEqual($hash) {
    $this->assertEquals($hash, $hash);
  }    

  #[@test]
  public function hashesOrderNotRelevant() {
    $hash= ['&' => '&amp;', '"' => '&quot;'];
    $this->assertEquals($hash, array_reverse($hash, true), Objects::stringOf($hash));
  }    

  #[@test, @values([1, 0, -1])]
  public function integerObjectsAreEqual($str) {
    $this->assertEquals(new Value($str), new Value($str));
  }

  #[@test, @values(['', 'Hello','äöüß'])]
  public function valuesAreEqual($str) {
    $this->assertEquals(new Name($str), new Name($str));
  }

  #[@test]
  public function nativeInstancesAreEqual() {
    $this->assertEquals(new \ReflectionClass(self::class), new \ReflectionClass(self::class));
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function differentNotTypesAreNotEqual() {
    $this->assertEquals(false, null);
  }    

  #[@test, @values([-1, 1.0, null, false, true, '', [[1]], new Value(1)])]
  public function integersAreNotEqual($cmp) {
    $this->assertNotEquals(1, $cmp);
  }    

  #[@test, @values([-1, 1.0, null, false, true, 1, [[1]], new Value(1)])]
  public function stringsAreNotEqual($cmp) {
    $this->assertNotEquals('', $cmp);
  }

  #[@test, @values([-1, 1.0, null, false, true, 1, [[1]], new Value(1)])]
  public function arraysAreNotEqual($cmp) {
    $this->assertNotEquals([], $cmp);
  }    

  #[@test, @expect(AssertionFailedError::class)]
  public function sameValuesAreEqual() {
    $this->assertNotEquals(1, 1);
  }    

  #[@test]
  public function nativeInstanceIsNotEqualToThis() {
    $this->assertNotEquals(new \ReflectionClass(self::class), $this);
  }

  #[@test]
  public function thisIsAnInstanceOfTestCase() {
    $this->assertInstanceOf(TestCase::class, $this);
  }

  #[@test]
  public function thisIsAnInstanceOfTestCaseClass() {
    $this->assertInstanceOf(\lang\XPClass::forName('unittest.TestCase'), $this);
  }    

  #[@test]
  public function thisIsAnInstanceOfObject() {
    $this->assertInstanceOf(\lang\Value::class, $this);
  }    

  #[@test]
  public function objectIsAnInstanceOfObject() {
    $this->assertInstanceOf(\lang\Value::class, new Value(2));
  }    

  #[@test, @expect(
  #  class= AssertionFailedError::class,
  #  withMessage= 'expected ["lang.Value"] but was ["int"]'
  #)]
  public function zeroIsNotAnInstanceOfValue() {
    $this->assertInstanceOf(\lang\Value::class, 0);
  }    

  #[@test, @expect(
  #  class= AssertionFailedError::class,
  #  withMessage= 'expected ["lang.Value"] but was ["void"]'
  #)]
  public function nullIsNotAnInstanceOfValue() {
    $this->assertInstanceOf(\lang\Value::class, null);
  }    

  #[@test, @expect(
  #  class= AssertionFailedError::class,
  #  withMessage= 'expected ["unittest.tests.Value"] but was ["unittest.tests.AssertionsTest"]'
  #)]
  public function thisIsNotAnInstanceOfValue() {
    $this->assertInstanceOf(Value::class, $this);
  }    

  #[@test]
  public function zeroIsInstanceOfInt() {
    $this->assertInstanceOf('int', 0);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function zeroPointZeroIsNotInstanceOfInt() {
    $this->assertInstanceOf('int', 0.0);
  }    

  #[@test]
  public function nullIsInstanceOfVar() {
    $this->assertInstanceOf(\lang\Type::$VAR, null);
  }    

  #[@test, @expect(AssertionFailedError::class)]
  public function nullIsNotInstanceOfVoidType() {
    $this->assertInstanceOf(\lang\Type::$VOID, null);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function nullIsNotInstanceOfVoid() {
    $this->assertInstanceOf('void', null);
  }

  #[@test]
  public function emptyArrayIsInstanceOfArray() {
    $this->assertInstanceOf('array', []);
  }

  #[@test]
  public function intArrayIsInstanceOfArray() {
    $this->assertInstanceOf('array', [1, 2, 3]);
  }

  #[@test]
  public function hashIsInstanceOfArray() {
    $this->assertInstanceOf('array', ['color' => 'green']);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function nullIsNotInstanceOfArray() {
    $this->assertInstanceOf('array', null);
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function arrayObjectIsNotInstanceOfArray() {
    $this->assertInstanceOf('array', new \ArrayObject([1, 2, 3]));
  }

  #[@test, @expect(AssertionFailedError::class)]
  public function primitiveIsNotAnInstanceOfValuelass() {
    $this->assertInstanceOf('int', new Value(1));
  }    
}
