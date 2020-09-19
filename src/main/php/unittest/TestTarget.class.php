<?php namespace unittest;

class TestTarget extends Test {

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
    return $compound ? nameof($this->instance).'::'.$this->method->name() : $this->method->name();
  }

  /** @return string */
  public function hashCode() {
    return md5(get_class($this->instance).':'.$this->method->name());
  }
}