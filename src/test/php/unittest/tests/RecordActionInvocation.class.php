<?php namespace unittest\tests;

use unittest\TestCase;

/**
 * This class is used in the TestActionTest 
 */
class RecordActionInvocation extends \lang\Object implements \unittest\TestAction {
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
   * @param  unittest.TestCase $t
   */
  public function beforeTest(TestCase $t) {
    $f= $t->getClass()->getField($this->field);
    $f->set($t, array_merge($f->get($t), ['before']));
  }

  /**
   * After test: Update field
   *
   * @param  unittest.TestCase $t
   */
  public function afterTest(TestCase $t) {
    $f= $t->getClass()->getField($this->field);
    $f->set($t, array_merge($f->get($t), ['after']));
  }
}
