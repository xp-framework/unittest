<?php namespace unittest\tests;
 
use lang\Error;
use unittest\{AssertionFailedError, PrerequisitesNotMetError, Test, TestAssertionFailed, TestCase, TestCaseInstance, TestError, TestExpectationMet, TestNotRun, TestPrerequisitesNotMet, TestSuite, TestVariation, TestWarning, Values};
use util\Objects;

/**
 * Test TestOutcome implementations
 *
 * @see      xp://unittest.TestOutcome
 */
class TestOutcomeTest extends TestCase {

  /**
   * Creates fixtures
   *
   * @return iterable
   */
  public function fixtures() {
    $test= new TestCaseInstance($this);
    return [
      [$test, ''],
      [new TestVariation($test, ['v']), '("v")']
    ];
  }

  /**
   * Assertion helper
   *
   * @param  string expected format string, %s will be replaced by compound name
   * @param  unittest.TestOutcome outcome 
   * @throws unittest.AssertionFailedError
   */
  protected function assertStringRepresentation($expected, $outcome, $variant) {
    $this->assertEquals(
      sprintf($expected, nameof($this).'::'.$this->getName().$variant),
      $outcome->toString()
    );
  }

  #[Test, Values('fixtures')]
  public function string_representation_of_TestExpectationMet($test, $variant) {
    $this->assertStringRepresentation(
      'unittest.TestExpectationMet(test= %s, time= 0.000 seconds)',
      new TestExpectationMet($test, 0.0),
      $variant
    );
  }

  #[Test, Values('fixtures')]
  public function string_representation_of_TestAssertionFailed($test, $variant) {
    $assert= new AssertionFailedError('Not equal', 1, 2);
    $this->assertStringRepresentation(
      "unittest.TestAssertionFailed(test= %s, time= 0.000 seconds) {\n  ".str_replace("\n", "\n  ", Objects::stringOf($assert))."\n}",
      new TestAssertionFailed($test, $assert, 0.0),
      $variant
    );
  }

  #[Test, Values('fixtures')]
  public function string_representation_of_TestError($test, $variant) {
    $error= new Error('Out of memory');
    $this->assertStringRepresentation(
      "unittest.TestError(test= %s, time= 0.000 seconds) {\n  ".str_replace("\n", "\n  ", Objects::stringOf($error))."\n}",
      new TestError($test, $error, 0.0),
      $variant
    );
  }

  #[Test, Values('fixtures')]
  public function string_representation_of_TestPrerequisitesNotMet($test, $variant) {
    $prerequisites= new PrerequisitesNotMetError('Initialization failed');
    $this->assertStringRepresentation(
      "unittest.TestPrerequisitesNotMet(test= %s, time= 0.000 seconds) {\n  ".str_replace("\n", "\n  ", Objects::stringOf($prerequisites))."\n}",
      new TestPrerequisitesNotMet($test, $prerequisites, 0.0),
      $variant
    );
  }

  #[Test, Values('fixtures')]
  public function string_representation_of_TestNotRun($test, $variant) {
    $this->assertStringRepresentation(
      "unittest.TestNotRun(test= %s, time= 0.000 seconds) {\n  \"Ignored\"\n}",
      new TestNotRun($test, 'Ignored', 0.0),
      $variant
    );
  }

  #[Test, Values('fixtures')]
  public function string_representation_of_TestWarning($test, $variant) {
    $this->assertStringRepresentation(
      "unittest.TestWarning(test= %s, time= 0.000 seconds) {\n".
      "  unittest.Warnings(1)@{\n".
      "    Could not open file\n".
      "  }\n".
      "}",
      new TestWarning($test, [[__FILE__, __LINE__, 'Could not open file']], 0.0),
      $variant
    );
  }
}