<?php namespace unittest;

/**
 * Indicates an `@ignore` annotation was present
 */
class IgnoredBecause extends \lang\XPException {
    
  /**
   * Constructor
   *
   * @param  string $value The annotation value
   */
  public function __construct($value) {
    parent::__construct($value ? (string)$value : 'n/a');
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
