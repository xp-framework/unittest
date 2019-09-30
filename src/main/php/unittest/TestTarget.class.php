<?php namespace unittest;

class TestTarget implements Test {
  public $instance, $method;

  public function __construct($instance, $method) {
    $this->instance= $instance;
    $this->method= $method;
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

  public function hashCode() {
    return md5(get_class($this->instance).':'.$this->method->getName());
  }

  /** @deprecated */
  private $case= null;

  /** @deprecated */
  public function asCase() {
    if (null === $this->case) {
      $name= $this->method->getName();
      $instance= $this->instance;
      $this->case= newinstance(TestCase::class, [$name], [
        $name => function() use($instance, $name) {
          return $instance->{$name}();
        }
      ]);
    }
    return $this->case;
  }
}