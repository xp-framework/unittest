<?php namespace unittest\metrics;

use lang\Runtime;

class MemoryUsed extends Metric {
  private $used, $peak;

  /** @return void */
  protected function calculate() {
    $rt= Runtime::getInstance();
    $this->used= $rt->memoryUsage();
    $this->peak= $rt->peakMemoryUsage();
  }

  /** @return string */
  protected function format() {
    return sprintf('%.2f kB (%.2f kB peak)', $this->used / 1024, $this->peak / 1024);
  }

  /** @return var */
  protected function value() {
    return $this->used;
  }
}