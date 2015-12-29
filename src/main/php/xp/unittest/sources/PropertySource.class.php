<?php namespace xp\unittest\sources;

use util\Properties;
use lang\XPClass;

/**
 * Source that load tests from a .ini file
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
    $section= $this->properties->getFirstSection();
    do {
      if ('this' === $section) continue;   // Ignore special section

      $suite->addTestClass(
        XPClass::forName($this->properties->readString($section, 'class')),
        $arguments ?: $this->properties->readArray($section, 'args')
      );
    } while ($section= $this->properties->getNextSection());
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
