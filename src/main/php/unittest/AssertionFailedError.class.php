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
   * @param  string|unittest.AssertionFailedMessage $message
   * @param  bool $omitFirstFrame
   */
  public function __construct($message, $omitFirstFrame= false) {
    if ($message instanceof AssertionFailedMessage) {
      parent::__construct($message->format());
    } else {
      parent::__construct((string)$message);
    }

    // Omit 1st element, this is always unittest.TestCase::fail()
    $omitFirstFrame && array_shift($this->trace);
    foreach ($this->trace as $element) {
      $element->args= null;
    }
  }

  /** @return unittest.TestOutcome */
  public function outcome(Test $test, Timer $timer) {
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