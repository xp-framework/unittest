<?php namespace unittest;

class TestTarget extends \lang\Object {
  private $instance;
  private $before, $after;

  public function __construct($instance, &$before, &$after) {
    $this->instance= $instance;
    $this->before= &$before;
    $this->after= &$after;
  }

  public function before() { return $this->before; }

  public function after() { return $this->after; }

  public function instance() { return $this->instance; }

  public function toString() {
    return nameof($this)."@{\n".
      "  before: ".($this->before ? \xp::stringOf($this->before, '  ') : '[]')."\n".
      "  test: ".$this->instance->toString().")\n".
      "  after: ".($this->after ? \xp::stringOf($this->after, '  '): '[]')."\n".
    "}";
  }
}