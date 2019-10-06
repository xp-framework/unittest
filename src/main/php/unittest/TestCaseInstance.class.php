<?php namespace unittest;

use lang\Throwable;

class TestCaseInstance extends Test {

  public function __construct($instance, $method= null, $actions= []) {
    parent::__construct($instance, $method ?: typeof($instance)->getMethod($instance->name), $actions);
  }

  /**
   * Invoke a block, wrap PHP5 and PHP7 native base exceptions in lang.Error
   *
   * @param  string $method
   * @return void
   */
  private function invoke($method) {
    try {
      $this->instance->{$method}();
    } catch (Throwable $e) {
      throw $e;
    } catch (\Exception $e) {
      throw Throwable::wrap($e);
    } catch (\Throwable $e) {
      throw Throwable::wrap($e);
    }
  }

  /**
   * Runs this testcase
   *
   * @param  var[] $args
   * @return void
   * @throws lang.Throwable
   */
  public function run($args) {
    try {
      $this->invoke('setUp');
      $this->method->invoke($this->instance, $args);
    } finally {
      $this->invoke('tearDown');
    }
  }

  /**
   * Get this test target's name
   *
   * @param  bool $compound whether to use compound format
   * @return string
   */
  public function getName($compound= false) {
    return $this->instance->getName($compound);
  }

  /** @return string */
  public function hashCode() {
    return $this->instance->hashCode();
  }
}