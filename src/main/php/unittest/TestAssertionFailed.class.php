<?php namespace unittest;

/**
 * Indicates a test failed
 *
 * @see   xp://unittest.TestFailure
 */
class TestAssertionFailed extends TestFailure {
  private $source= null;

  /**
   * Constructor
   *
   * @param  unittest.Test $test
   * @param  unittest.AssertionFailedError|unittest.AssertionFailedMessage|string $reason
   * @param  double $elapsed
   */
  public function __construct(Test $test, $reason, $elapsed) {
    parent::__construct($test, $elapsed);
    $this->reason= $reason instanceof AssertionFailedError ? $reason : new AssertionFailedError($reason);
  }

  /**
   * Set this assertion failure's source back to the given source
   *
   * @param  var[] $source
   * @return self
   */
  public function at($source) {
    $this->source= $source;
    return $this;
  }

  /**
   * Trace this assertion failure back to a given originating exception
   *
   * @param  lang.Throwable $exception
   * @return self
   */
  public function trace($exception) {
    $this->source= $this->sourceOf($exception);
    return $this;
  }

  /** @return var[] */
  public function source() { return $this->source ?? $this->sourceOf($this->reason); }

  /** @return string */
  public function event() { return 'testFailed'; }

  /** @return string */
  protected function formatReason() { return $this->reason->toString(); }

}