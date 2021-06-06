<?php namespace xp\unittest;

trait Colors {
  private $colored= null;

  /**
   * Set color
   *
   * @see    https://www.php.net/manual/de/function.stream-isatty.php
   * @param  ?bool $enable
   * @return void
   */
  public function setColor($enable) {
    $this->colored= $enable ?? (
      $this->out instanceof ConsoleOutputStream &&
      function_exists('stream_isatty') ? stream_isatty(STDOUT) :
      (function_exists('posix_isatty') ? posix_isatty(STDOUT) : true)
    );
  }

  /**
   * Set color
   *
   * @param  ?bool $enable
   * @return self
   */
  public function withColor($enable) {
    $this->setColor($enable);
    return $this;
  }
}