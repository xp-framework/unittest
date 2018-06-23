<?php namespace unittest\tests;

use unittest\TestCase;

abstract class AbstractSourceTest extends TestCase {

  /**
   * Assertion helper
   *
   * @param  string[] $expected Class names
   * @param  xp.unittest.sources.AbstractSource $source
   * @throws unittest.AssertionFailedError
   */
  protected function assertFinds($expected, $source) {
    $found= [];
    foreach ($source->classes() as $class) {
      $found[]= $class->getName();
    }
    sort($found);
    $this->assertEquals($expected, $found);
  }
}