<?php namespace unittest\tests;

use io\streams\MemoryInputStream;
use lang\IllegalArgumentException;
use unittest\Test;
use util\Properties;
use xp\unittest\sources\PropertySource;

class PropertySourceTest extends AbstractSourceTest {

  /**
   * Creates a properties object from a given source string
   *
   * @param  string... $source
   * @return util.Properties
   */
  private function properties(... $source) {
    $p= new Properties();
    $p->load(new MemoryInputStream(implode("\n", $source)));
    return $p;
  }

  #[Test]
  public function can_create() {
    new PropertySource($this->properties(''));
  }

  #[Test]
  public function sections_are_iterated() {
    $this->assertTests(
      ['unittest.tests.sources.InBase::test', 'unittest.tests.sources.util.InUtil::test'],
      new PropertySource($this->properties(
        '[util]',
        'class=unittest.tests.sources.util.InUtil',
        '[base]',
        'class=unittest.tests.sources.InBase'
      ))
    );
  }

  #[Test]
  public function special_this_section_is_not_iterated() {
    $this->assertTests(
      ['unittest.tests.sources.InBase::test'],
      new PropertySource($this->properties(
        '[this]',
        'description="Base tests"',
        '[base]',
        'class=unittest.tests.sources.InBase'
      ))
    );
  }
}