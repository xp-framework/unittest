<?php namespace xp\unittest\sources;

use lang\ClassLoader;
use util\Properties;

/**
 * Source that load tests from a .ini file
 *
 * @test  xp://unittest.tests.PropertySourceTest
 */
class PropertySource extends AbstractSource {
  private $properties, $description;
  
  /**
   * Constructor
   *
   * @param  util.Properties $properties
   */
  public function __construct(Properties $properties) {
    $this->properties= $properties;
    $this->description= $this->properties->readString('this', 'description', 'Tests');
  }

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   */
  public function provideTo($suite, $arguments) {
    $cl= ClassLoader::getDefault();

    foreach ($this->properties->sections() as $section) {
      if ('this' === $section) continue;   // Ignore special section

      $suite->addTestClass(
        $cl->loadClass($this->properties->readString($section, 'class')),
        $arguments ?: $this->properties->readArray($section, 'args')
      );
    }
  }

  /**
   * Creates a string representation of this source
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'['.$this->description.' @ '.$this->properties->getFilename().']';
  }
}