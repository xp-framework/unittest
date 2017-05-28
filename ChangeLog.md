Unittests change log
====================

## ?.?.? / ????-??-??

## 9.0.1 / 2017-05-28

* Merged PR #20: Drop dependency on xp-framework/io-collections,
  preventing a circular-dependency situation.
  (@thekid)

## 9.0.0 / 2017-05-28

* Merged PR #19: XP9 Compatibility - @thekid

## 8.0.0 / 2017-05-25

* Dropped support for PHP 5.5 - @thekid

## 7.2.0 / 2017-05-20

* Refactored code to use `typeof()` instead of `xp::typeOf()`, see
  https://github.com/xp-framework/rfc/issues/323
  (@thekid)

## 7.1.1 / 2016-09-20

* Fixed "Class xp\unittest\QuietListener does not have a constructor,
  so you cannot pass any constructor arguments".
  (@thekid, @kiesel)

## 7.1.0 / 2016-08-28

* Added forward compatibility with XP 8.0.0 - @thekid

## 7.0.2 / 2016-06-18

* Changed detection whether to use colors in the output to check
  whether writing to the console; and no longer check `TERM` or
  `ANSICON` environment variables. The XP runners guarantee ANSI
  color escape sequences work in all situations!
  (@thekid)

## 7.0.1 / 2016-03-17

* Fixed issue when expected exception's message was empty. Originally
  reported in xp-framework/core#135 by @kiesel
  (@thekid)

## 7.0.0 / 2016-02-21

* **Adopted semantic versioning. See xp-framework/rfc#300** - @thekid 
* Added version compatibility with XP 7 - @thekid

## 6.10.1 / 2016-01-23

* Fix code to use `nameof()` instead of the deprecated `getClassName()`
  method from lang.Generic. See xp-framework/core#120
  (@thekid)

## 6.10.0 / 2016-01-10

* **Heads up: Upgrade your runners before using this release!**
  It uses class path precedence as defined in xp-runners/reference#11
  (@thekid)

## 6.9.0 / 2016-01-10

* **Heads up: Bumped minimum XP version required to XP 6.10.0** - @thekid
* Merged PR #14: Use a "TL;DR" style for displaying help. This will show
  when used together with the new XP runners xp-framework/rfc#303
  (@thekid)

## 6.8.3 / 2016-01-09

* Fix issue #16: Fatal error when no testcases are found - @thekid

## 6.8.2 / 2016-01-05

* Fixed incorrect class name for ColoredBarListener which rendered it
  unusable. It now works again as expected, use the following options:
  `unittest -q -l ColoredBar - src/test/php`
  (@thekid)

## 6.8.1 / 2016-01-05

* Fixed issue #13: assertEquals() and non-XP objects - @thekid

## 6.8.0 / 2016-01-03

* Merged pull request #12: Use symbols that also work in Windows console
  (@thekid)
* Added integration with new XP subcommand runners: `xp test [args]`.
  See xp-framework/rfc#303
  (@thekid)
* **Heads up: Bumped minimum XP version required to XP 6.9.1** - @thekid
* Merged pull request #7: Wrap native exceptions - @thekid
* Merged pull request #9: Refactor sources - @thekid

## 6.7.2 / 2015-12-29

* Merged pull request #11: Add "stop after first failing test" option
  (@thekid)

## 6.7.1 / 2015-12-29

* Merged pull request #10: Refactor: Provide test cases which greatly
  simplifies code inside source implementations and removes duplication
  (@thekid)

## 6.7.0 / 2015-12-13

* Merged pull request #6: Refactor: Actions' before and after runlogic
  (@thekid)
* Changed `fail()` to also work without actual and expected parameters
  (@thekid)
* Changed test case execution to catch PHP5 and PHP7 base exceptions
  from test setup and teardown and make them fail tests.
  See https://github.com/xp-framework/xp-framework/pull/382
  (@thekid)

## 6.6.2 / 2015-12-13

* Added dependency on io.collections - @thekid

## 6.6.1 / 2015-12-11

* Removed dependency on util.collections - @thekid

## 6.6.0 / 2015-11-08

* Fixed forward compatibility with XP 6.6.0 - @thekid
* Changed `TestSuite::addTestClass()` to return class added instead of
  list of ignored test methods. The latter is dependant on the internal
  implementation and also not used anywhere.
  (@thekid)

## 6.5.0 / 2015-09-27

* **Heads up: Bumped minimum PHP version required to PHP 5.5**. See PR #4
  (@thekid)

## 6.4.2 / 2015-08-06

* MFH: Fixed `unittest.XmlTestListener::uriFor()` raising exceptions - @thekid
* **Heads up: Split library from xp-framework/core as per xp-framework/rfc#293**
  (@thekid)
