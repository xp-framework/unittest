<?php namespace unittest;

class ExpectedMessageDiffers implements AssertionFailedMessage {
  private $expected, $thrown;

  /**
   * Constructor
   *
   * @param   string $expected
   * @param   lang.Throwable $thrown
   */
  public function __construct($expected, $thrown) {
    $this->expected= $expected;
    $this->thrown= $thrown;
  }


  /**
   * Return formatted message
   *
   * @return  string
   */
  public function format() {
    return sprintf(
      'Expected %s\'s message "%s" differs from expected %s',
      nameof($this->thrown),
      $this->thrown->getMessage(),
      $this->expected
    );
  }
}