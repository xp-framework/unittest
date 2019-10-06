<?php namespace unittest\tests;

use unittest\Test;

/**
 * This class is used in the TestActionTest 
 */
class RecordActionInvocation implements \unittest\TestAction {
  protected $field= null;

  /**
   * Constructor
   *
   * @param string $field
   */
  public function __construct($field) {
    $this->field= $field;
  }

  /**
   * Before test: Update field
   *
   * @param  unittest.Test $t
   */
  public function beforeTest(Test $t) {
    $f= typeof($t->instance)->getField($this->field);
    $f->set($t->instance, array_merge($f->get($t->instance), ['before']));
  }

  /**
   * After test: Update field
   *
   * @param  unittest.Test $t
   */
  public function afterTest(Test $t) {
    $f= typeof($t->instance)->getField($this->field);
    $f->set($t->instance, array_merge($f->get($t->instance), ['after']));
  }
}
