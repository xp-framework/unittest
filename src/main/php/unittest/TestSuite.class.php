<?php namespace unittest;

use lang\{IllegalArgumentException, Value, XPClass};
use util\Objects;

/**
 * Test suite
 *
 * @test   xp://net.xp_framework.unittest.tests.SuiteTest
 * @test   xp://net.xp_framework.unittest.tests.ListenerTest
 * @test   xp://net.xp_framework.unittest.tests.BeforeAndAfterClassTest
 * @see    http://junit.sourceforge.net/doc/testinfected/testing.htm
 */
class TestSuite implements Value {
  private $listeners= [];
  private $sources= [];

  /**
   * Add a test
   *
   * @param  unittest.TestCase $test
   * @return unittest.TestCase
   * @throws lang.IllegalArgumentException in case given argument is not a testcase
   * @throws lang.IllegalStateException for overriding test class methods with tests
   * @throws lang.MethodNotImplementedException in case given argument is not a valid testcase
   */
  public function addTest(TestCase $test) {
    $this->sources[get_class($test)][]= new TestInstance($test);
    return $test;
  }

  /**
   * Add a test class
   *
   * @param  string|lang.XPClass $class
   * @param  var[] $arguments default [] arguments to pass to test case constructor
   * @return lang.XPClass
   * @throws lang.IllegalArgumentException in case given argument is not a testcase class
   * @throws util.NoSuchElementException in case given testcase class does not contain any tests
   */
  public function addTestClass($class, $arguments= []) {
    $type= $class instanceof XPClass ? $class : XPClass::forName($class);
    if ($type->isSubclassOf(TestCase::class)) {
      $this->sources[$type->literal()][]= new TestClass($type, $arguments);
    } else {
      $this->sources[$type->literal()][]= new TestTargets($type, $arguments);
    }
    return $type;
  }

  /**
   * Returns number of tests in this suite
   *
   * @return int
   */
  public function numTests() {
    $numTests= 0;
    foreach ($this->sources as $classname => $groups) {
      foreach ($groups as $group) {
        $numTests+= $group->numTests();
      }
    }
    return $numTests;
  }

  /**
   * Remove all tests
   *
   * @return void
   */
  public function clearTests() {
    $this->sources= [];
  }

  /**
   * Returns test at a given position
   *
   * @param  int $pos
   * @return unittest.Test or NULL if none was found
   */
  public function testAt($pos) {
    $num= 0;
    foreach ($this->sources as $classname => $groups) {
      foreach ($groups as $group) {
        foreach ($group->tests() as $test) {
          if ($num++ === $pos) return $test;
        }
      }
    }
    return null;
  }

  /**
   * Returns all tests
   *
   * @return iterable
   */
  public function tests() {
    foreach ($this->sources as $classname => $groups) {
      foreach ($groups as $group) {
        foreach ($group->tests() as $test) {
          yield $test;
        }
      }
    }
  }

  /**
   * Adds a listener
   *
   * @param  unittest.Listener $l
   * @return unittest.Listener the added listener
   */
  public function addListener($l) {
    if ($l instanceof Listener) {
      $this->listeners[]= $l;
    } else {
      $this->listeners[]= new ListenerAdapter($l);  // Deprecated usage
    }
    return $l;
  }

  /**
   * Removes a listener
   *
   * @param  unittest.Listener $l
   * @return bool TRUE if the listener was removed, FALSE if not.
   */
  public function removeListener(Listener $l) {
    for ($i= 0, $s= sizeof($this->listeners); $i < $s; $i++) {
      if ($this->listeners[$i] !== $l) continue;

      // Found the listener, remove it and re-index the listeners array
      unset($this->listeners[$i]);
      $this->listeners= array_values($this->listeners);
      return true;
    }
    return false;
  }

  /**
   * Run given sources
   *
   * @param  function(unittest.TestRun): void $target
   * @return unittest.TestResult
   */
  private function runThis($target) {
    $run= new TestRun(new TestResult(), $this->listeners);

    $run->start($this);
    try {
      $target($run);
      $run->finish($this);
    } catch (StopTests $stop) {
      $run->abort($this, $stop);
    }

    return $run->result();
  }

  /**
   * Run a single test
   *
   * @param  lang.XPClass|unittest.TestGroup|unittest.TestCase $test
   * @return unittest.TestResult
   * @throws lang.IllegalArgumentException in case given argument is not a testcase
   * @throws lang.MethodNotImplementedException in case given argument is not a valid testcase
   */
  public function runTest($test) {
    if ($test instanceof TestCase) {
      $f= function($run) use($test) { $run->one(new TestInstance($test)); };
    } else if ($test instanceof TestGroup) {
      $f= function($run) use($test) { $run->one($test); };
    } else if ($test instanceof XPClass) {
      $f= function($run) use($test) { $run->one(new TestTargets($test)); };
    } else {
      throw new IllegalArgumentException('Expecting lang.XPClass|unittest.TestGroup|unittest.TestCase, have '.typeof($test));
    }
    return $this->runThis($f);
  }

  /**
   * Run this test suite
   *
   * @return unittest.TestResult
   */
  public function run() {
    return $this->runThis(function($run) {
      foreach ($this->sources as $classname => $groups) {
        $run->all($groups);
      }
    });
  }

  /** @return string */
  public function toString() {
    $s= nameof($this).'['.sizeof($this->sources)."]@{\n";
    foreach ($this->sources as $classname => $groups) {
      foreach ($groups as $group) {
        $s.= '  '.nameof($group).'<'.$group->type().'>: '.$group->numTests()." test(s)\n";
      }
    }
    return $s.'}';
  }

  /** @return string */
  public function hashCode() {
    return 'S'.Objects::hashOf($this->sources);
  }

  /**
   * Compares this test suite to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->sources, $value->sources) : 1;
  }
}