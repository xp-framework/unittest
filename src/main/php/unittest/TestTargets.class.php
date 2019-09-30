<?php namespace unittest;

use lang\IllegalArgumentException;
use lang\reflect\TargetInvocationException;

class TestTargets extends TestGroup {
  private $type;
  private $instances= null;

  static function __static() { }

  /**
   * Creates an instance from a type
   *
   * @param  lang.XPClass $type
   */
  public function __construct($type) {
    if (!$type->reflect()->isInstantiable()) {
      throw new IllegalArgumentException('Cannot instantiate '.$type->getName());
    }
    $this->type= $type;
  }

  private function instances() {
    if (null === $this->instances) {
      $this->instances= [];
      $instance= $this->type->newInstance();
      foreach ($this->type->getMethods() as $method) {
        if ($method->hasAnnotation('test')) {
          $name= $method->getName();
          $this->instances[$name]= newinstance(TestCase::class, [$name], [
            $name => function() use($instance, $method) {
              try {
                return $method->invoke($instance, []);
              } catch (TargetInvocationException $e) {
                throw $e->getCause();
              }
            }
          ]);
        }
      }
    }
    return $this->instances;
  }

  /** @return lang.XPClass */
  public function type() { return $this->type; }

  /** @return int */
  public function numTests() { return sizeof($this->instances()); }

  /** @return iterable */
  public function tests() { return array_values($this->instances()); }

  /** @return iterable */
  public function targets() {
    foreach ($this->instances() as $name => $instance) {
      yield new Target($name, $instance);
    }
  }
}