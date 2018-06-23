<?php namespace unittest\tests\sources\util;

use unittest\TestCase;

abstract class Base extends TestCase {

  #[@test]
  public function test() {
    $this->assertTrue(true);
  }
}