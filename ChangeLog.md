Unittests change log
====================

## ?.?.? / ????-??-??

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
