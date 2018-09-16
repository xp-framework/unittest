<?php namespace unittest\tests;

use unittest\TestCase;
use unittest\TestResult;
use unittest\metrics\MemoryUsed;
use unittest\metrics\Metric;
use unittest\metrics\TimeTaken;

class MetricsTest extends TestCase {

  #[@test]
  public function formatted_calls_calculate_once() {
    $metric= new TestMetric(0);
    $this->assertEquals(['+1', '+1'], [$metric->formatted(), $metric->formatted()]);
  }

  #[@test]
  public function formatted_can_refresh_calculation() {
    $metric= new TestMetric(0);
    $this->assertEquals(['+1', '+2'], [$metric->formatted(), $metric->formatted(true)]);
  }

  #[@test]
  public function calculated_calls_calculate_once() {
    $metric= new TestMetric(0);
    $this->assertEquals([1, 1], [$metric->calculated(), $metric->calculated()]);
  }

  #[@test]
  public function calculated_can_refresh_calculation() {
    $metric= new TestMetric(0);
    $this->assertEquals([1, 2], [$metric->calculated(), $metric->calculated(true)]);
  }

  #[@test]
  public function string_representation() {
    $metric= new TestMetric(6100);
    $this->assertEquals('unittest.tests.TestMetric<+6101>', $metric->toString());
  }

  #[@test]
  public function hash_code() {
    $metric= new TestMetric(6100);
    $this->assertEquals('Mba2c5278f196f791a788e7b0d4498ed7', $metric->hashCode());
  }

  #[@test]
  public function comparing_to_another_instance() {
    $this->assertEquals(1, (new TestMetric(0))->compareTo($this));
  }

  #[@test, @values([
  #  [0, 0],
  #  [1, -1],
  #  [6100, -1],
  #  [-1, 1],
  #  [-6100, 1]
  #])]
  public function comparing_two_metrics($counter, $expected) {
    $this->assertEquals($expected, (new TestMetric(0))->compareTo(new TestMetric($counter)));
  }

  #[@test]
  public function time_taken() {
    $t= new TimeTaken(new TestResult());
    $this->assertEquals(0.0, $t->calculated());
  }

  #[@test]
  public function memory_used() {
    $t= new MemoryUsed();
    $this->assertEquals(memory_get_usage(), $t->calculated());
  }
}