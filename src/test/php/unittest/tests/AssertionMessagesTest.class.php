<?php namespace unittest\tests;

use unittest\actions\RuntimeVersion;
use unittest\{ComparisonFailedMessage, Test, TestCase};

/**
 * TestCase
 *
 * @see   xp://unittest.ComparisonFailedMessage
 */
class AssertionMessagesTest extends TestCase {

  /**
   * Assertion helper
   *
   * @param   string expected
   * @param   unittest.ComparisonFailedMessage $message
   * @throws  unittest.AssertionFailedError
   */
  protected function assertFormatted($expected, $message) {
    $this->assertEquals($expected, $message->format());
  }


  #[Test]
  public function differentIntegerPrimitives() {
    $this->assertFormatted(
      'expected [2] but was [1] using: \'equals\'',
      new ComparisonFailedMessage('equals', 2, 1)
    );
  }

  #[Test]
  public function differentBoolPrimitives() {
    $this->assertFormatted(
      'expected [true] but was [false] using: \'equals\'',
      new ComparisonFailedMessage('equals', true, false)
    );
  }

  #[Test]
  public function differentPrimitives() {
    $this->assertFormatted(
      'expected [int:2] but was [bool:false] using: \'equals\'',
      new ComparisonFailedMessage('equals', 2, false)
    );
  }

  #[Test]
  public function differentStringPrimitives() {
    $this->assertFormatted(
      'expected ["Hello"] but was ["World"] using: \'equals\'',
      new ComparisonFailedMessage('equals', 'Hello', 'World')
    );
  }

  #[Test]
  public function differentTypes() {
    $this->assertFormatted(
      'expected [unittest.tests.Value(1)] but was [int:1] using: \'equals\'',
      new ComparisonFailedMessage('equals', new Value(1), 1)
    );
  }

  #[Test]
  public function twoArrays() {
    $this->assertFormatted(
      "expected [[1, 2]] but was [[2, 3]] using: 'equals'",
      new ComparisonFailedMessage('equals', [1, 2], [2, 3])
    );
  }

  #[Test]
  public function twoObjects() {
    $this->assertFormatted(
      "expected [unittest.TestCase<a>] but was [unittest.TestCase<b>] using: 'equals'",
      new ComparisonFailedMessage('equals', new TestCase('a'), new TestCase('b'))
    );
  }

  #[Test]
  public function nullVsObject() {
    $this->assertFormatted(
      "expected [unittest.TestCase<b>] but was [null] using: 'equals'",
      new ComparisonFailedMessage('equals', new TestCase('b'), null)
    );
  }

  #[Test]
  public function nullVsString() {
    $this->assertFormatted(
      "expected [string:\"NULL\"] but was [null] using: 'equals'",
      new ComparisonFailedMessage('equals', 'NULL', null)
    );
  }

  #[Test]
  public function differentStringsWithCommonLeadingPart() {
    $prefix= str_repeat('*', 100);
    $this->assertFormatted(
      'expected ["...abc"] but was ["...def"] using: \'equals\'',
      new ComparisonFailedMessage('equals', $prefix.'abc', $prefix.'def')
    );
  }

  #[Test]
  public function differentStringsWithCommonTrailingPart() {
    $postfix= str_repeat('*', 100);
    $this->assertFormatted(
      'expected ["abc..."] but was ["def..."] using: \'equals\'',
      new ComparisonFailedMessage('equals', 'abc'.$postfix, 'def'.$postfix)
    );
  }

  #[Test]
  public function differentStringsWithCommonLeadingAndTrailingPart() {
    $prefix= str_repeat('<', 100);
    $postfix= str_repeat('>', 100);
    $this->assertFormatted(
      'expected ["...abc..."] but was ["...def..."] using: \'equals\'',
      new ComparisonFailedMessage('equals', $prefix.'abc'.$postfix, $prefix.'def'.$postfix)
    );
  }

  #[Test]
  public function prefixShorterThanContextLength() {
    $this->assertFormatted(
      'expected ["abc!"] but was ["abc."] using: \'equals\'',
      new ComparisonFailedMessage('equals', 'abc!', 'abc.')
    );
  }

  #[Test]
  public function postfixShorterThanContextLength() {
    $this->assertFormatted(
      'expected ["!abc"] but was [".abc"] using: \'equals\'',
      new ComparisonFailedMessage('equals', '!abc', '.abc')
    );
  }
}