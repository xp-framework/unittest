<?php namespace unittest;

class TestTarget extends Test {
  public $instance, $method;

  public function __construct($instance, $method) {
    $this->instance= $instance;
    $this->method= $method;
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
   * Runs this testcase
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