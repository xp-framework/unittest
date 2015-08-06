Unittests
=========

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-framework/unittest.svg)](http://travis-ci.org/xp-framework/unittest)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_4plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Supports HHVM 3.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/hhvm-3_4plus.png)](http://hhvm.com/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/unittest/version.png)](https://packagist.org/packages/xp-framework/unittest)

Unittests for the XP Framework.

Writing a test
--------------
Tests reside inside a testcase class and are annotated with the `@test` annotation.

```php
class CalculatorTest extends \unittest\TestCase {

  #[@test]
  public function addition() {
    $this->assertEquals(2, 1 + 1);
  }
}
```

To run the test, use the `unittest` runner:

```sh
$ unittest CalculatorTest
[.]

✓: 1/1 run (0 skipped), 1 succeeded, 0 failed
Memory used: 1173.51 kB (1307.40 kB peak)
Time taken: 0.000 seconds
```

Assertion methods
-----------------
The unittest package provides the following six assertion methods:

```php
public void assertEquals(var $expected, var $actual, [string $error= "equals"])
public void assertNotEquals(var $expected, var $actual, [string $error= "!equals"])
public void assertTrue(var $actual, [string $error= "==="])
public void assertFalse(var $actual, [string $error= "==="])
public void assertNull(var $actual, [string $error= "==="])
public void assertInstanceOf(var $type, var $actual, [string $error= "instanceof"])
```

If you need more than that, you can use [xp-forge/assert](https://github.com/xp-forge/assert) on top of this library.

Setup and teardown
------------------
In order to run a method before and after every test, overwrite the base class' `setUp()` and `tearDown()` methods:

```php
class CalculatorTest extends \unittest\TestCase {
  private $fixture;

  /* @return void */
  public function setUp() {
    $this->fixture= new Calculator();
  }

  /* @return void */
  public function tearDown() {
    unset($this->fixture);
  }

  #[@test]
  public function addition() {
    $this->assertEquals(2, $this->fixture->add(1, 1));
  }
}
```

*Note: The `unset` above isn't really necessary, a fresh instance of the testcase class is created before every run and disposed thereafter, thus PHP's garbage collection takes care of freeing all members.*

Further reading
---------------

* [XP RFC #0150: Before and after methods for test cases](https://github.com/xp-framework/rfc/issues/150)
* [XP RFC #0145: Make unittests strict](https://github.com/xp-framework/rfc/issues/145)
* [XP RFC #0059: Timeouts for unittests](https://github.com/xp-framework/rfc/issues/59)
* [XP RFC #0020: Metadata for unittests](https://github.com/xp-framework/rfc/issues/20)
