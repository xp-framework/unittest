Unittests
=========

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-framework/unittest.svg)](http://travis-ci.org/xp-framework/unittest)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.6+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_6plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/unittest/version.png)](https://packagist.org/packages/xp-framework/unittest)

Unittests for the XP Framework

Writing a test
--------------
Tests reside inside a class and are annotated with the `@test` annotation.

```php
use unittest\Assert;

class CalculatorTest {

  #[@test]
  public function addition() {
    Assert::equals(2, 1 + 1);
  }
}
```

To run the test, use the `test` subcommand:

```sh
$ xp test CalculatorTest
[.]

♥: 1/1 run (0 skipped), 1 succeeded, 0 failed
Memory used: 1672.58 kB (1719.17 kB peak)
Time taken: 0.000 seconds
```

Assertion methods
-----------------
The unittest package provides the following six assertion methods:

```php
public abstract class unittest.Assert {
  public static void equals(var $expected, var $actual, string $error)
  public static void notEquals(var $expected, var $actual, string $error)
  public static void true(var $actual, string $error)
  public static void false(var $actual, string $error)
  public static void null(var $actual, string $error)
  public static void instance(string|lang.Type $type, var $actual, string $error)
}
```

*If you need more than that, you can use [xp-forge/assert](https://github.com/xp-forge/assert) on top of this library.*

Setup and teardown
------------------
In order to run a method before and after the tests are run, annotate methods with the `@before` and `@after` annotations:

```php
use unittest\Assert;

class CalculatorTest {
  private $fixture;

  #[@before]
  public function newFixture() {
    $this->fixture= new Calculator();
  }

  #[@after]
  public function cleanUp() {
    unset($this->fixture);
  }

  #[@test]
  public function addition() {
    Assert::equals(2, $this->fixture->add(1, 1));
  }
}
```

*Note: All test methods are run with the same instance of CalculatorTest!*

Expected exceptions
-------------------
The `@expect` annotation is a shorthand for catching exceptions and verifying their type manually.

```php
use lang\IllegalArgumentException;

class CalculatorTest {

  #[@test, @expect(IllegalArgumentException::class)]
  public function cannot_divide_by_zero() {
    (new Calculator())->divide(1, 0);
  }
}
```

Ignoring tests
--------------
The `@ignore` annotation can be used to ignore tests. This can be necessary as a temporary measure or when overriding a test base class and not wanting to run one of its methods.

```php
class EncodingTest {

  #[@test, @ignore('Does not work with all iconv implementations')]
  public function transliteration() {
    /* ... */
  }
}
```

Parameterization
-----------------
The `@values` annotation can be used to run a test with a variety of values which are passed as parameters.

```php
use lang\IllegalArgumentException;

class CalculatorTest {

  #[@test, @expect(IllegalArgumentException::class), @values([1, 0, -1])]
  public function cannot_divide_by_zero($dividend) {
    (new Calculator())->divide($dividend, 0);
  }
}
```

Actions
-------
To execute code before and after tests, test actions can be used. The unittest library comes with the following built-in actions:

* `unittest.actions.ExtensionAvailable(string $extension)` - Verifies a given PHP extension is loaded.
* `unittest.actions.IsPlatform(string $platform)` - Verifies tests are running on a given platform via case-insensitive match on `PHP_OS`. Prefix with `!` to invert.
* `unittest.actions.RuntimeVersion(string $version)` - Verifies tests are running on a given PHP runtime. See [version_compare](http://php.net/version_compare) for valid syntax.
* `unittest.actions.VerifyThat(function(): var|string $callable)` - Runs the given function, verifying it neither raises an exception nor return a false value.

```php
use unittest\actions\{IsPlatform, VerifyThat};

class FileSystemTest {

  #[@test, @action(new IsPlatform('!WIN'))
  public function not_run_on_windows() {
    // ...
  }

  #[@test, @action(new VerifyThat(function() {
  #  return file_exists('/$Recycle.Bin');
  #}))
  public function run_when_recycle_bin_exists() {
    // ...
  }
}
```

Multiple actions can be run around a test by passing an array to the *@action* annotation.

Further reading
---------------

* [XP RFC #0283: Unittest closure actions](https://github.com/xp-framework/rfc/issues/283)
* [XP RFC #0272: Unittest actions](https://github.com/xp-framework/rfc/issues/272)
* [XP RFC #0267: Unittest parameterization](https://github.com/xp-framework/rfc/issues/267)
* [XP RFC #0188: Test outcome](https://github.com/xp-framework/rfc/issues/188)
* [XP RFC #0187: @expect withMessage](https://github.com/xp-framework/rfc/issues/187)
* [XP RFC #0150: Before and after methods for test cases](https://github.com/xp-framework/rfc/issues/150)
* [XP RFC #0145: Make unittests strict](https://github.com/xp-framework/rfc/issues/145)
* [XP RFC #0059: Timeouts for unittests](https://github.com/xp-framework/rfc/issues/59)
* [XP RFC #0032: Add annotations for Unittest API](https://github.com/xp-framework/rfc/issues/32)
* [XP RFC #0020: Metadata for unittests](https://github.com/xp-framework/rfc/issues/20)
