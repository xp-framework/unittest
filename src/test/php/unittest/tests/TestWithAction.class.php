<?php namespace unittest\tests;

use unittest\{Test, TestCase};

/** This class is used in the TestActionTest */
#[Action(eval: 'new RecordActionInvocation("run")')]
class TestWithAction extends TestCase {
  public $run= [];

  #[Test]
  public function one() {
    $this->run[]= 'one';
  }

  #[Test]
  public function two() {
    $this->run[]= 'two';
  }
}