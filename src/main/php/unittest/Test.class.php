<?php namespace unittest;

use lang\{Value, Reflect, XPClass};

abstract class Test implements Value {
  public $instance, $method, $actions;

  public function __construct($instance, $method, $actions= []) {
    $this->instance= $instance;
    $this->method= $method;
    $this->actions= $actions;
  }

  /**
   * Get this test's name
   *
   * @param  bool $compound whether to use compound format
   * @return string
   */
  public abstract function getName($compound= false);

  /**
   * Creates a hashcode of this testcase
   *
   * @return string
   */
  public abstract function hashCode();

  /** @return ?string */
  public function ignored() {
    if ($annotation= $this->method->annotation(Ignore::class)) {
      return $annotation->argument(0) ?? '(n/a)';
    }
    return null;
  }

  /** @return ?int */
  public function timeLimit() {
    if ($annotation= $this->method->annotation(Limit::class)) {

    // Support both `Limit(time: ...)` and `Limit(['time' => ...])`
      return $annotation->argument('time') ?? $annotation->argument(0)['time'];
    }
    return null;
  }

  /** @return ?var[] */
  public function expected() {
    if (null === ($annotation= $this->method->annotation(Expect::class))) return null;

    // Support both `Expect(class: ...)` and `Expect(['class' => ...])`
    $arguments= $annotation->arguments();
    if (isset($arguments['class'])) {
      $class= $arguments['class'];
      $message= $arguments['withMessage'] ?? '';
    } else if (is_array($arguments[0])) {
      $class= $arguments[0]['class'];
      $message= $arguments[0]['withMessage'];
    } else {
      $class= $arguments[0];
      $message= '';
    }

    if ('' === $message || '/' === $message[0]) {
      $pattern= $message;
    } else {
      $pattern= '/'.preg_quote($message, '/').'/';
    }

    return [Reflect::of($class), $pattern];
  }

  /** @return iterable */
  public function variations() {
    if ($annotation= $this->method->annotation(Values::class)) {
      foreach ($this->valuesFor($this->instance, $annotation->argument(0)) as $args) {
        yield new TestVariation($this, is_array($args) ? $args : [$args]);
      }
    } else {
      yield $this;
    }
  }

  /**
   * Returns values
   *
   * @param  object $test
   * @param  var $annotation
   * @return var values a traversable structure
   */
  protected function valuesFor($test, $annotation) {
    if (!is_array($annotation)) {               // values("source")
      $source= $annotation;
      $args= [];
    } else if (isset($annotation['map'])) {     // values(map= ["test" => true, ...])
      $values= [];
      foreach ($annotation['map'] as $key => $value) {
        $values[]= [$key, $value];
      }
      return $values;
    } else if (isset($annotation['source'])) {  // values(source= "src" [, args= ...])
      $source= $annotation['source'];
      $args= $annotation['args'] ?? [];
    } else {                                    // values([1, 2, 3])
      return $annotation;
    }

    // Route "ClassName::methodName" -> static method of the given class,
    // "self::method" -> static method of the test class, and "method" 
    // -> the run test's instance method
    $p= strpos($source, '::');
    if (false === $p) {
      return Reflect::of($test)->method($source)->invoke($test, $args, $test);
    }

    $type= substr($source, 0, $p);
    $reflect= 'self' === $type ? Reflect::of($test) : Reflect::of($type);
    return $reflect->method(substr($source, $p + 2))->invoke(null, $args, $test);
  }

  /**
   * Creates a string representation of this testcase
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'<'.$this->getName(true).'>';
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? strcmp($this->getName(true), $value->getName(true)) : 1;
  }
}