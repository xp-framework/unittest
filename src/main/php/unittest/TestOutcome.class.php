<?php namespace unittest;

/**
 * Outcome from a test
 *
 */
interface TestOutcome extends \lang\Value {

  /**
   * Returns elapsed time
   *
   * @return  float
   */
  public function elapsed();

}
