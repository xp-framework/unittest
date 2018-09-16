<?php namespace unittest\tests;

use unittest\metrics\Metric;

class TestMetric extends Metric {
  private $counter;

  /** @param int $counter */
  public function __construct($counter) { $this->counter= $counter; }

  /** @return void */
  protected function calculate() { $this->counter++; }

  /** @return var */
  protected function value() { return $this->counter; }

  /** @return string */
  protected function format() { return sprintf('+%d', $this->counter); }

}