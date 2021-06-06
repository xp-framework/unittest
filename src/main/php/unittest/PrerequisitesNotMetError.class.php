<?php namespace unittest;

use lang\Throwable;
use util\Objects;
use util\profiling\Timer;

/**
 * Indicates prerequisites have not been met
 *
 * @see  xp://unittest.TestPrerequisitesNotMet
 */
class PrerequisitesNotMetError extends TestAborted {
  public $prerequisites= [];
    
  /**
   * Constructor
   *
   * @param  string $message
   * @param  lang.Throwable $cause 
   * @param  var[] $prerequisites default []
   */
  public function __construct($message, Throwable $cause= null, $prerequisites= []) {
    parent::__construct($message, $cause);
    $this->prerequisites= (array)$prerequisites;
  }

  /** @return unittest.TestOutcome */
  public function outcome(Test $test, Timer $timer) {
    return new TestPrerequisitesNotMet($test, $this, $timer->elapsedTime());
  }

  /**
   * Return compound message of this exception.
   *
   * @return string
   */
  public function compoundMessage() {
    return sprintf(
      '%s (%s) { prerequisites: %s }',
      nameof($this),
      $this->message,
      Objects::stringOf($this->prerequisites)
    );
  }
}