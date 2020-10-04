<?php namespace unittest\tests;
 
use lang\XPClass;
use unittest\{AssertionFailedError, Expect, Test, TestCase, Values};
use util\Objects;

/**
 * Test assertion methods
 */
class AssertionsTest extends TestCase {

  #[Test]
  public function trueIsTrue() {
    $this->assertTrue(true);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function falseIsNotTrue() {
    $this->assertTrue(false);
  }

  #[Test]
  public function falseIsFalse() {
    $this->assertFalse(false);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function trueIsNotFalse() {
    $this->assertFalse(true);
  }

  #[Test]
  public function nullIsNull() {
    $this->assertNull(null);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function falseIsNotNull() {
    $this->assertNull(false);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function zeroIsNotNull() {
    $this->assertNull(0);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function emptyStringIsNotNull() {
    $this->assertNull('');
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function emptyArrayIsNotNull() {
    $this->assertNull([]);
  }

  #[Test]
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

  #[Test, Values([0, 1, -1, PHP_INT_MAX])]
  public function integersAreEqual($int) {
    $this->assertEquals($int, $int);
  }

  #[Test, Values(['', 'Hello', 'äöüß'])]
  public function stringsAreEqual($str) {
    $this->assertEquals($str, $str);
  }

  #[Test, Values(eval: '[[[]], [[1, 2, 3]], [[[1], [], [-1, 4], [new Value(2)]]]]')]
  public function arraysAreEqual($array) {
    $this->assertEquals($array, $array);
  }

  #[Test, Values(eval: '[[[]], [["foo" => 2]], [[["bar" => "baz"], [], ["bool" => true, "bar" => new Value(6100)]]]]')]
  public function hashesAreEqual($hash) {
    $this->assertEquals($hash, $hash);
  }

  #[Test]
  public function hashesOrderNotRelevant() {
    $hash= ['&' => '&amp;', '"' => '&quot;'];
    $this->assertEquals($hash, array_reverse($hash, true), Objects::stringOf($hash));
  }

  #[Test, Values([1, 0, -1])]
  public function integerObjectsAreEqual($str) {
    $this->assertEquals(new Value($str), new Value($str));
  }

  #[Test, Values(['', 'Hello','äöüß'])]
  public function valuesAreEqual($str) {
    $this->assertEquals(new Name($str), new Name($str));
  }

  #[Test]
  public function nativeInstancesAreEqual() {
    $this->assertEquals(new \ReflectionClass(self::class), new \ReflectionClass(self::class));
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function differentNotTypesAreNotEqual() {
    $this->assertEquals(false, null);
  }

  #[Test, Values(eval: '[-1, 1.0, null, false, true, "", [[1]], new Value(1)]')]
  public function integersAreNotEqual($cmp) {
    $this->assertNotEquals(1, $cmp);
  }

  #[Test, Values(eval: '[-1, 1.0, null, false, true, 1, [[1]], new Value(1)]')]
  public function stringsAreNotEqual($cmp) {
    $this->assertNotEquals('', $cmp);
  }

  #[Test, Values(eval: '[-1, 1.0, null, false, true, 1, [[1]], new Value(1)]')]
  public function arraysAreNotEqual($cmp) {
    $this->assertNotEquals([], $cmp);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function sameValuesAreEqual() {
    $this->assertNotEquals(1, 1);
  }

  #[Test]
  public function nativeInstanceIsNotEqualToThis() {
    $this->assertNotEquals(new \ReflectionClass(self::class), $this);
  }

  #[Test]
  public function thisIsAnInstanceOfTestCase() {
    $this->assertInstanceOf(TestCase::class, $this);
  }

  #[Test]
  public function thisIsAnInstanceOfTestCaseClass() {
    $this->assertInstanceOf(XPClass::forName('unittest.TestCase'), $this);
  }

  #[Test]
  public function thisIsAnInstanceOfObject() {
    $this->assertInstanceOf('lang.Value', $this);
  }

  #[Test]
  public function objectIsAnInstanceOfObject() {
    $this->assertInstanceOf('lang.Value', new Value(2));
  }

  #[Test, Expect(class: AssertionFailedError::class, withMessage: 'expected ["lang.Value"] but was ["int"]')]
  public function zeroIsNotAnInstanceOfValue() {
    $this->assertInstanceOf('lang.Value', 0);
  }

  #[Test, Expect(class: AssertionFailedError::class, withMessage: 'expected ["lang.Value"] but was ["void"]')]
  public function nullIsNotAnInstanceOfValue() {
    $this->assertInstanceOf('lang.Value', null);
  }

  #[Test, Expect(class: AssertionFailedError::class, withMessage: 'expected ["unittest.tests.Value"] but was ["unittest.tests.AssertionsTest"]')]
  public function thisIsNotAnInstanceOfValue() {
    $this->assertInstanceOf(Value::class, $this);
  }

  #[Test]
  public function zeroIsInstanceOfInt() {
    $this->assertInstanceOf('int', 0);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function zeroPointZeroIsNotInstanceOfInt() {
    $this->assertInstanceOf('int', 0.0);
  }

  #[Test]
  public function nullIsInstanceOfVar() {
    $this->assertInstanceOf(\lang\Type::$VAR, null);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function nullIsNotInstanceOfVoidType() {
    $this->assertInstanceOf(\lang\Type::$VOID, null);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function nullIsNotInstanceOfVoid() {
    $this->assertInstanceOf('void', null);
  }

  #[Test]
  public function emptyArrayIsInstanceOfArray() {
    $this->assertInstanceOf('array', []);
  }

  #[Test]
  public function intArrayIsInstanceOfArray() {
    $this->assertInstanceOf('array', [1, 2, 3]);
  }

  #[Test]
  public function hashIsInstanceOfArray() {
    $this->assertInstanceOf('array', ['color' => 'green']);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function nullIsNotInstanceOfArray() {
    $this->assertInstanceOf('array', null);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function arrayObjectIsNotInstanceOfArray() {
    $this->assertInstanceOf('array', new \ArrayObject([1, 2, 3]));
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function primitiveIsNotAnInstanceOfValuelass() {
    $this->assertInstanceOf('int', new Value(1));
  }
}