<?php namespace unittest\tests;

use io\Folder;
use lang\IllegalArgumentException;
use unittest\{Expect, Test, TestSuite};
use xp\unittest\sources\FolderSource;

class FolderSourceTest extends AbstractSourceTest {

  #[Test]
  public function can_create() {
    new FolderSource(new Folder('src/test/php'));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function raises_error_when_created_with_non_class_path() {
    new FolderSource(new Folder('/'));
  }

  #[Test]
  public function finds_classes() {
    $this->assertTests(
      [
        'unittest.tests.sources.InBase::test',
        'unittest.tests.sources.util.InUtil::test',
        'unittest.tests.sources.util.LDAPTest::connect'
      ],
      new FolderSource(new Folder('src/test/php/unittest/tests/sources'))
    );
  }

  #[Test]
  public function finds_classes_in_subpackage() {
    $this->assertTests(
      ['unittest.tests.sources.util.InUtil::test', 'unittest.tests.sources.util.LDAPTest::connect'],
      new FolderSource(new Folder('src/test/php/unittest/tests/sources/util'))
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function raises_error_when_no_tests_found() {
    $f= new FolderSource(new Folder('src/test/php/unittest/tests/sources/fixtures/'));
    $f->provideTo(new TestSuite(), $arguments= []);
  }
}