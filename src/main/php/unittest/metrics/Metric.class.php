<?php namespace unittest\metrics;

use lang\Value;
use util\Objects;

/**
 * Base class for metrics. Subclasses overwrite the `calculate()`, `format()`
 * and `value()` methods.
 *
 * @test  xp://unittest.tests.MetricsTest
 */
abstract class Metric implements Value {
  private $calculated= false;

  /**
   * Calculates this metric's values
   *
   * @return void
   */
  protected abstract function calculate();

  /**
   * Formats this metric as a string. The `calculate()` method is
   * guaranteed to have been called!
   *
   * @return string
   */
  protected abstract function format();

  /**
   * Returns this metric's value, which is used for comparison. The
   * `calculate()` method is guaranteed to have been called!
   *
   * @return var
   */
  protected abstract function value();

  /**
   * Retrieve a formatted version of this metric
   *
   * @param  bool $refresh Defaults to using cached values if available
   * @return string
   */
  public function formatted($refresh= false) {
    if ($refresh || !$this->calculated) {
      $this->calculate();
      $this->calculated= true;
    }
    return $this->format();
  }

  /**
   * Retrieve the calculated value of this metric
   *
   * @param  bool $refresh Defaults to using cached values if available
   * @return var
   */
  public function calculated($refresh= false) {
    if ($refresh || !$this->calculated) {
      $this->calculate();
      $this->calculated= true;
    }
    return $this->value();
  }

  /** @return string */
  public function toString() { return nameof($this).'<'.$this->formatted().'>'; }

  /** @return string */
  public function hashCode() { return 'M'.md5($this->formatted()); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare($this->calculated(), $value->calculated())
      : 1
    ;
  }
}