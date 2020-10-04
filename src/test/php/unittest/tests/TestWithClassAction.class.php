<?php namespace unittest\tests;

use unittest\{Action, Test, TestCase};

/** This class is used in the TestClassActionTest */
#[Action(eval: 'new RecordActionInvocation("run")')]
class TestWithClassAction extends TestCase {
  public static $run= [];

  #[Test]
  public function fixture() {
    self::$run[]= 'test';
  }
}