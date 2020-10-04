<?php namespace unittest\tests;

use lang\{IllegalAccessException, IllegalArgumentException, Throwable};
use unittest\{Assert, AssertionFailedError, Expect, Test, Values};

class AssertTest {

  /** @return iterable */
  private function values() {
    return [
      [0], [1], [-1],
      [''], ['Test'],
      [true], [false], [null],
      [[]], [[1, 2, 3]], [['key' => 'value']],
      [function() { }],
      [new Value('Test')],
    ];
  }

  #[Test, Values('values')]
  public function values_equal_themselves($value) {
    Assert::equals($value, $value);
  }

  #[Test, Values('values')]
  public function values_do_not_equal_this($value) {
    Assert::notEquals($this, $value);
  }

  #[Test]
  public function true_equals() {
    Assert::true(true);
  }

  #[Test]
  public function false_equals() {
    Assert::false(false);
  }

  #[Test]
  public function null_equals() {
    Assert::null(null);
  }

  #[Test]
  public function instanceof_self() {
    Assert::instance(self::class, $this);
  }

  #[Test]
  public function instanceof_subclass() {
    Assert::instance(Value::class, newinstance(Value::class, [$this], []));
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function equals_raises_error_when_not_equal() {
    Assert::equals(1, 2);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function notEquals_raises_error_when_equal() {
    Assert::notEquals(1, 1);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function true_raises_error_when_not_true() {
    Assert::true(false);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function false_raises_error_when_not_false() {
    Assert::false(true);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function null_raises_error_when_not_null() {
    Assert::null($this);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function instanceof_raises_error_when_given_non_object() {
    Assert::instance(self::class, null);
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function instanceof_raises_error_when_given_non_instance() {
    Assert::instance(Value::class, $this);
  }

  #[Test]
  public function catch_expected() {
    Assert::throws(IllegalAccessException::class, function() {
      throw new IllegalAccessException('Test');
    });
  }

  #[Test]
  public function catch_subclass_of_expected() {
    Assert::throws(Throwable::class, function() {
      throw new IllegalAccessException('Test');
    });
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function no_exception_thrown() {
    Assert::throws(IllegalAccessException::class, function() {
      // NOOP
    });
  }

  #[Test, Expect(AssertionFailedError::class)]
  public function different_exception_thrown() {
    Assert::throws(IllegalAccessException::class, function() {
      throw new IllegalArgumentException('Test');
    });
  }
}