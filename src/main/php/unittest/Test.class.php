<?php namespace unittest;

use lang\{Value, XPClass};

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

  /** @return var */
  public function declaration() {
    return [$this->method->_reflect->getFileName(), $this->method->_reflect->getStartLine()];
  }

  /** @return ?string */
  public function ignored() {
    return $this->method->hasAnnotation('ignore') ? ($this->method->getAnnotation('ignore') ?: '(n/a)') : null;
  }

  /** @return ?int */
  public function timeLimit() {
    return $this->method->hasAnnotation('limit') ? $this->method->getAnnotation('limit', 'time') : 0;
  }

  /** @return ?var[] */
  public function expected() {
    if ($this->method->hasAnnotation('expect', 'class')) {
      $message= $this->method->getAnnotation('expect', 'withMessage');
      if ('' === $message || '/' === $message[0]) {
        $pattern= $message;
      } else {
        $pattern= '/'.preg_quote($message, '/').'/';
      }
      return [XPClass::forName($this->method->getAnnotation('expect', 'class')), $pattern];
    } else if ($this->method->hasAnnotation('expect')) {
      return [XPClass::forName($this->method->getAnnotation('expect')), null];
    } else {
      return null;
    }
  }

  /** @return iterable */
  public function variations() {
    if ($this->method->hasAnnotation('values')) {
      foreach ($this->valuesFor($this->instance, $this->method->getAnnotation('values')) as $args) {
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
    if (false === ($p= strpos($source, '::'))) {
      return typeof($test)->getMethod($source)->setAccessible(true)->invoke($test, $args);
    }

    $ref= substr($source, 0, $p);
    if ('self' === $ref) {
      $class= typeof($test);
    } else if (strstr($ref, '.')) {
      $class= XPClass::forName($ref);
    } else {
      $class= new XPClass($ref);
    }
    return $class->getMethod(substr($source, $p + 2))->invoke(null, $args);
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