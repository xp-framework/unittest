<?php namespace unittest;

use util\profiling\Timer;

/**
 * Indicates an assertion failed
 *
 * @see  xp://unittest.AssertionFailedMessage
 */
class AssertionFailedError extends TestAborted {

  /**
   * Constructor
   *
   * @param   var message
   */
  public function __construct($message) {
    if ($message instanceof AssertionFailedMessage) {
      parent::__construct($message->format());
    } else {
      parent::__construct((string)$message);
    }

    // Omit 1st element, this is always unittest.TestCase::fail()
    array_shift($this->trace);
    foreach ($this->trace as $element) {
      $element->args= null;
    }
  }

  /** @return string */
  public function type() { return 'testFailed'; }

  /** @return unittest.TestOutcome */
  public function outcome(TestCase $test, Timer $timer) {
    return new TestAssertionFailed($test, $this, $timer->elapsedTime());
  }

  /**
   * Return compound message of this exception.
   *
   * @return  string
   */
  public function compoundMessage() {
    return nameof($this).'{ '.$this->message.' }';
  }
}
