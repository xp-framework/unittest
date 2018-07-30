<?php namespace unittest\tests;

use lang\IllegalArgumentException;
use lang\reflect\Package;
use unittest\TestSuite;
use xp\unittest\sources\PackageSource;

class PackageSourceTest extends AbstractSourceTest {

  #[@test]
  public function can_create() {
    new PackageSource(Package::forName('unittest.tests.sources'), $recursive= false);
  }

  #[@test]
  public function finds_classes_inside_given_package() {
    $this->assertTests(
      ['unittest.tests.sources.InBase::test'],
      new PackageSource(Package::forName('unittest.tests.sources'), $recursive= false)
    );
  }

  #[@test]
  public function finds_classes_inside_given_package_recursively() {
    $this->assertTests(
      [
        'unittest.tests.sources.InBase::test',
        'unittest.tests.sources.util.InUtil::test',
        'unittest.tests.sources.util.LDAPTest::connect'
      ],
      new PackageSource(Package::forName('unittest.tests.sources'), $recursive= true)
    );
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function raises_error_when_no_tests_found() {
    $f= new PackageSource(Package::forName('unittest.tests.sources.fixtures'), $recursive= false);
    $f->provideTo(new TestSuite(), $arguments= []);
  }
}