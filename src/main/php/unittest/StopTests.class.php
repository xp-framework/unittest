<?php namespace unittest;

class StopTests extends \lang\XPException {

  /**
   * Constructor
   *
   * @param  lang.Throwable $reason
   */
  public function __construct($reason) {
    parent::__construct($reason->compoundMessage());
  }

  /**
   * Return compound message of this exception.
   *
   * @return string
   */
  public function compoundMessage() {
    return nameof($this).'('.$this->message.')';
  }
}
