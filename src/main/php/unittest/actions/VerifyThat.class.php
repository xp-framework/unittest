<?php namespace unittest\actions;

use lang\{MethodNotImplementedException, Throwable, XPClass};
use unittest\{PrerequisitesNotMetError, TestAction, TestCase, TestClassAction};

/**
 * Verifies a certain callable works
 *
 * @test  xp://net.xp_framework.unittest.tests.VerifyThatTest
 */
class VerifyThat implements TestAction, TestClassAction {
  protected $verify, $prerequisite;

  /**
   * Create a new verification
   *
   * @param  (function(): var)|string $callable
   */
  public function __construct($callable) {
    if ($callable instanceof \Closure) {
      $this->verify= $callable;
      $this->prerequisite= '<function()>';
    } else if (0 === strncmp($callable, 'self::', 6)) {
      $method= substr($callable, 6);
      $this->verify= function() use($method) {
        if (method_exists(get_called_class(), $method)) return self::$method();
        throw new MethodNotImplementedException('No such method', $method);
      };
      $this->prerequisite= $callable;
    } else if (false !== ($p= strpos($callable, '::'))) {
      $class= literal(substr($callable, 0, $p));
      $method= substr($callable, $p + 2);
      $this->verify= function() use($class, $method) {
        if (method_exists($class, $method)) return $class::$method();
        throw new MethodNotImplementedException('No such method', $method);
      };
      $this->prerequisite= $callable;
    } else {
      $this->verify= function() use($callable) {
        if (method_exists($this, $callable)) return $this->$callable();
        throw new MethodNotImplementedException('No such method', $callable);
      };
      $this->prerequisite= '$this->'.$callable;
    }
  }

  /**
   * This method gets invoked before a test method is invoked, and before
   * the setUp() method is called.
   *
   * @param  unittest.TestCase $t
   * @throws unittest.PrerequisitesNotMetError
   */
  public function beforeTest(TestCase $t) {
    try {
      $verified= $this->verify->bindTo($t, $t)->__invoke();
    } catch (Throwable $e) {
      throw new PrerequisitesNotMetError('Verification raised '.$e->compoundMessage(), null, [$this->prerequisite]);
    }

    if (!$verified) {
      throw new PrerequisitesNotMetError('Verification of failed', null, [$this->prerequisite]);
    }
  }

  /**
   * This method gets invoked after the test method is invoked and regard-
   * less of its outcome, after the tearDown() call has run.
   *
   * @param  unittest.TestCase $t
   */
  public function afterTest(TestCase $t) {
    // Empty
  }

  /**
   * This method gets invoked before any test method of the given class is
   * invoked, and before any methods annotated with beforeTest.
   *
   * @param  lang.XPClass $c
   * @return void
   * @throws unittest.PrerequisitesNotMetError
   */
  public function beforeTestClass(XPClass $c) {
    if (0 === strncmp($this->prerequisite, '$this', 5)) {
      throw new PrerequisitesNotMetError('Cannot use instance methods on a class action', null, [$this->prerequisite]);
    }

    try {
      $verified= $this->verify->bindTo  (null, $c->literal())->__invoke();
    } catch (Throwable $e) {
      throw new PrerequisitesNotMetError('Verification raised '.$e->compoundMessage(), null, [$this->prerequisite]);
    }

    if (!$verified) {
      throw new PrerequisitesNotMetError('Verification of failed', null, [$this->prerequisite]);
    }
  }

  /**
   * This method gets invoked after all test methods of a given class have
   * executed, and after any methods annotated with afterTest
   *
   * @param  lang.XPClass $c
   * @return void
   */
  public function afterTestClass(XPClass $c) {
    // Empty
  }
}