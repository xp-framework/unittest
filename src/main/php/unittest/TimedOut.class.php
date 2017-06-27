<?php namespace unittest;

class TimedOut implements AssertionFailedMessage {
  private $expected, $taken;

  /**
   * Constructor
   *
   * @param   double $expected
   * @param   double $taken
   */
  public function __construct($expected, $taken) {
    $this->expected= $expected;
    $this->taken= $taken;
  }


  /**
   * Return formatted message
   *
   * @return  string
   */
  public function format() {
    return sprintf(
      'Test runtime of %.3f seconds longer than eta of %.3f seconds',
      $this->taken,
      $this->expected
    );
  }
}