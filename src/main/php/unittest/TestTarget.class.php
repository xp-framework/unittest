<?php namespace unittest;

class TestTarget extends Test {
  public $instance, $method, $actions;

  public function __construct($instance, $method, $actions= []) {
    $this->instance= $instance;
    $this->method= $method;
    $this->actions= $actions;
  }

  /** @return [:var] */
  public function annotations() {
    $return= [];
    foreach ($this->method->getAnnotations() as $name => $value) {
      $return[$name]= [$value];
    }
    return $return;
  }

  /**
   * Runs this test target
   *
   * @param  var[] $args
   * @return void
   * @throws lang.Throwable
   */
  public function run($args) {
    $this->method->invoke($this->instance, $args);
  }

  /**
   * Get this test target's name
   *
   * @param  bool $compound whether to use compound format
   * @return string
   */
  public function getName($compound= false) {
    return $compound ? nameof($this->instance).'::'.$this->method->getName() : $this->method->getName();
  }

  /** @return string */
  public function hashCode() {
    return md5(get_class($this->instance).':'.$this->method->getName());
  }
}