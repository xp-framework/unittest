<?php namespace unittest;

abstract class Errors {

  /** @return void */
  public static function clear() { \xp::gc(); }
  
  /**
   * Returns all errors in registry
   *
   * @return string[]
   */
  public static function raised() {
    $w= [];
    foreach (\xp::$errors as $file => $lookup) {
      foreach ($lookup as $line => $messages) {
        foreach ($messages as $message => $detail) {
          $w[]= sprintf(
            '"%s" in %s::%s() (%s, line %d, occured %s)',
            $message,
            $detail['class'],
            $detail['method'],
            basename($file),
            $line,
            1 === $detail['cnt'] ? 'once' : $detail['cnt'].' times'
          );
        }
      }
    }
    return $w;
  }
}