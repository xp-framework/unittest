<?php namespace unittest;

use io\streams\OutputStreamWriter;
use lang\XPClass;
use unittest\Listener;
use util\Objects;
use util\collections\HashTable;
use xml\Tree;

/**
 * Creates an XML file suitable for importing into continuous integration
 * systems like Hudson.
 *
 * @test  xp://net.xp_framework.unittests.tests.XmlListenerTest
 */
class XmlTestListener implements Listener {
  public $out= null;
  protected $tree= null;
  protected $classes= [];
  
  /**
   * Constructor
   *
   * @param   io.streams.OutputStreamWriter out
   */
  public function __construct(OutputStreamWriter $out) {
    $this->out= $out;
    $this->tree= new Tree('testsuites');
  }

  /**
   * Tries to get class uri via reflection
   *
   * @param  lang.XPClass class The class to return the URI for
   * @return string
   */
  private function uriFor(XPClass $class) {
    return $class->getClassLoader()->classUri($class->getName());
  }

  /**
   * Tries to get method start line
   *
   * @param lang.XPClass $class
   * @param string $methodname
   * @return int
   */
  private function lineFor(XPClass $class, $methodname) {
    try {
      return $class->reflect()->getMethod($methodname)->getStartLine();
    } catch (\Exception $ignored) {
      return 0;
    }
  }
  
  /**
   * Return message error string for given error
   * 
   * @param unittest.TestOutcome error The error
   * @return string
   */
  protected function messageFor(TestOutcome $error) {
    $testClass= $error->test->getClass();
    
    return sprintf(
      "%s(%s)\n%s \n\n%s:%d\n\n",
      $testClass->getName(),
      $error->test->getName(),
      Objects::stringOf($error->reason),
      $this->uriFor($testClass),
      $this->lineFor($testClass, $error->test->getName())
    );
  }

  /**
   * Called when a test case starts.
   *
   * @param  unittest.TestStart $start
   */
  public function testStarted(TestStart $start) {
    // NOOP
  }

  /**
   * Add test result node, if it does not yet exist.
   *
   * @param   unittest.TestCase case
   * @return  xml.Node
   */
  protected function testNode(TestCase $case) {
    $class= nameof($case);
    if (!isset($this->classes[$class])) {
      $this->classes[$class]= $this->tree->addChild(new \xml\Node('testsuite', null, [
        'name'       => $class->getName(),
        'file'       => $this->uriFor($class),
        'tests'      => 0,
        'failures'   => 0,
        'errors'     => 0,
        'skipped'    => 0,
        'time'       => 0
      ]));
    }

    return $this->classes[$class];
  }
  
  /**
   * Add test case information node and update suite information.
   *
   * @param   unittest.TestOutcome outcome
   * @param   string inc
   * @return  xml.Node
   */
  protected function addTestCase(TestOutcome $outcome, $inc= null) {
    $testClass= $outcome->test->getClass();
    
    // Update test count
    $n= $this->testNode($outcome->test);
    $n->setAttribute('tests', $n->getAttribute('tests')+ 1);
    $n->setAttribute('time', $n->getAttribute('time')+ $outcome->elapsed());
    $inc && $n->setAttribute($inc, $n->getAttribute($inc)+ 1);
    
    // Add testcase information
    return $n->addChild(new \xml\Node('testcase', null, [
      'name'       => $outcome->test->getName(),
      'class'      => $testClass->getName(),
      'file'       => $this->uriFor($testClass),
      'line'       => $this->lineFor($testClass, $outcome->test->getName()),
      'time'       => sprintf('%.6f', $outcome->elapsed)
    ]));
  }

  /**
   * Called when a test fails.
   *
   * @param   unittest.TestFailure failure
   */
  public function testFailed(TestFailure $failure) {
    $t= $this->addTestCase($failure, 'failures');
    $t->addChild(new \xml\Node('failure', $this->messageFor($failure), [
      'message' => trim($failure->reason->compoundMessage()),
      'type'    => typeof($failure->reason)->getName()
    ]));
  }

  /**
   * Called when a test errors.
   *
   * @param   unittest.TestError error
   */
  public function testError(TestError $error) {
    $t= $this->addTestCase($error, 'errors');
    $t->addChild(new \xml\Node('error', $this->messageFor($error), [
      'message' => trim($error->reason->compoundMessage()),
      'type'    => typeof($error->reason)->getName()
    ]));
  }

  /**
   * Called when a test raises warnings.
   *
   * @param   unittest.TestWarning warning
   */
  public function testWarning(TestWarning $warning) {
    $t= $this->addTestCase($warning, 'errors');
    $t->addChild(new \xml\Node('error', $this->messageFor($warning), [
      'message' => 'Non-clear error stack',
      'type'    => 'warnings'
    ]));
  }
  
  /**
   * Called when a test finished successfully.
   *
   * @param   unittest.TestSuccess success
   */
  public function testSucceeded(TestSuccess $success) {
    $this->addTestCase($success);
  }
  
  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param   unittest.TestSkipped skipped
   */
  public function testSkipped(TestSkipped $skipped) {
    if ($skipped->reason instanceof \lang\Throwable) {
      $reason= trim($skipped->reason->compoundMessage());
    } else {
      $reason= $skipped->reason;
    }
    $this->addTestCase($skipped, 'skipped')->setAttribute('skipped', $reason);
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param   unittest.TestSkipped ignore
   */
  public function testNotRun(TestSkipped $ignore) {
    $this->addTestCase($ignore, 'skipped')->setAttribute('skipped', $ignore->reason);
  }

  /**
   * Called when a test run starts.
   *
   * @param   unittest.TestSuite suite
   */
  public function testRunStarted(TestSuite $suite) {
    // NOOP
  }
  
  /**
   * Called when a test run finishes.
   *
   * @param   unittest.TestSuite suite
   * @param   unittest.TestResult result
   */
  public function testRunFinished(TestSuite $suite, TestResult $result) {
    $this->out->write($this->tree->getDeclaration()."\n");
    $this->out->write($this->tree->getSource(INDENT_DEFAULT));
  }
}