<?php namespace unittest\tests;

use lang\XPClass;

/**
 * This class is used in the TestClassActionTest 
 */
class RecordClassActionInvocation implements \unittest\TestClassAction {
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
   * Before test class: Update field
   *
   * @param  lang.XPClass $c
   */
  public function beforeTestClass(XPClass $c) {
    $f= $c->getField($this->field);
    $f->set(null, array_merge($f->get(null), ['before']));
  }

  /**
   * After test class: Update "run" field
   *
   * @param  lang.XPClass $c
   */
  public function afterTestClass(XPClass $c) {
    $f= $c->getField('run');
    $f->set(null, array_merge($f->get(null), ['after']));
  }
}