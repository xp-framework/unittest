<?php namespace unittest\tests;

use lang\Runtime;
use unittest\metrics\{MemoryUsed, Metric, TimeTaken};
use unittest\{Test, TestCase, TestResult, Values};

class MetricsTest extends TestCase {

  #[Test]
  public function formatted_calls_calculate_once() {
    $metric= new TestMetric(0);
    $this->assertEquals(['+1', '+1'], [$metric->formatted(), $metric->formatted()]);
  }

  #[Test]
  public function formatted_can_refresh_calculation() {
    $metric= new TestMetric(0);
    $this->assertEquals(['+1', '+2'], [$metric->formatted(), $metric->formatted(true)]);
  }

  #[Test]
  public function calculated_calls_calculate_once() {
    $metric= new TestMetric(0);
    $this->assertEquals([1, 1], [$metric->calculated(), $metric->calculated()]);
  }

  #[Test]
  public function calculated_can_refresh_calculation() {
    $metric= new TestMetric(0);
    $this->assertEquals([1, 2], [$metric->calculated(), $metric->calculated(true)]);
  }

  #[Test]
  public function string_representation() {
    $metric= new TestMetric(6100);
    $this->assertEquals('unittest.tests.TestMetric<+6101>', $metric->toString());
  }

  #[Test]
  public function hash_code() {
    $metric= new TestMetric(6100);
    $this->assertEquals('Mba2c5278f196f791a788e7b0d4498ed7', $metric->hashCode());
  }

  #[Test]
  public function comparing_to_another_instance() {
    $this->assertEquals(1, (new TestMetric(0))->compareTo($this));
  }

  #[Test, Values([[0, 0], [1, -1], [6100, -1], [-1, 1], [-6100, 1]])]
  public function comparing_two_metrics($counter, $expected) {
    $this->assertEquals($expected, (new TestMetric(0))->compareTo(new TestMetric($counter)));
  }

  #[Test]
  public function time_taken() {
    $t= new TimeTaken(new TestResult());
    $this->assertEquals(0.0, $t->calculated());
  }

  #[Test]
  public function memory_used() {
    $t= new MemoryUsed(newinstance(Runtime::class, [], [
      'memoryUsage'     => function() { return 6100; },
      'peakMemoryUsage' => function() { return 9999; },
    ]));
    $this->assertEquals(6100, $t->calculated());
  }
}