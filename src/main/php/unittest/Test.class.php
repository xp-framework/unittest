<?php namespace unittest;

interface Test {

  /**
   * Get this test target's name
   *
   * @param  bool $compound whether to use compound format
   * @return string
   */
  public function getName($compound= false);

  public function hashCode();

}