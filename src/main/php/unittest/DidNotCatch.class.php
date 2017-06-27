<?php namespace unittest;

class DidNotCatch implements AssertionFailedMessage {
  private $expected, $thrown;

  /**
   * Constructor
   *
   * @param   lang.XPClass $expected
   * @param   lang.Throwable $thrown
   */
  public function __construct($expected, $thrown= null) {
    $this->expected= $expected;
    $this->thrown= $thrown;
  }


  /**
   * Return formatted message
   *
   * @return  string
   */
  public function format() {
    if ($this->thrown) {
      return 'Caught '.$this->thrown->compoundMessage().' instead of expected '.$this->expected->getName();
    } else {
      return 'Expected '.$this->expected->getName().' not caught';
    }
  }
}