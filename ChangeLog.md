Unittests change log
====================

## ?.?.? / ????-??-??

## 6.7.0 / 2015-12-11

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
