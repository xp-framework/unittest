<?php namespace unittest\tests;

use unittest\{TestCase, TestSuite};

abstract class AbstractSourceTest extends TestCase {

  /**
   * Assertion helper
   *
   * @param  string[] $expected Class names
   * @param  xp.unittest.sources.AbstractSource $source
   * @throws unittest.AssertionFailedError
   */
  protected function assertTests($expected, $source) {
    $suite= new TestSuite();
    $source->provideTo($suite, []);

    $contained= [];
    foreach ($suite->tests() as $test) {
      $contained[]= $test->getName(true);
    }
    sort($contained);
    $this->assertEquals($expected, $contained);
  }
}