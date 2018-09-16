<?php namespace unittest\metrics;

class TimeTaken extends Metric {
  private $result, $elapsed;

  /** @param unittest.TestResult $result */
  public function __construct($result) {
    $this->result= $result;
  }

  /** @return void */
  protected function calculate() {
    $this->elapsed= $this->result->elapsed();
  }

  /** @return string */
  protected function format() {
    return sprintf('%.3f seconds', $this->elapsed);
  }

  /** @return var */
  protected function value() {
    return $this->elapsed;
  }
}