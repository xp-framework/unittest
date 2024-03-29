<?php namespace xp\unittest;

use io\streams\OutputStreamWriter;
use lang\Enum;

/** Listeners enumeration */
abstract class TestListeners extends Enum {
  public static $COMPACT, $VERBOSE, $QUIET, $BAR;
  public static $DEFAULT;
  
  static function __static() {
    self::$COMPACT= newinstance(__CLASS__, [0, 'COMPACT'], '{
      static function __static() { }
      public function getImplementation() {
        return \lang\XPClass::forName("xp.unittest.DefaultListener");
      }
    }');
    self::$VERBOSE= newinstance(__CLASS__, [1, 'VERBOSE'], '{
      static function __static() { }
      public function getImplementation() {
        return \lang\XPClass::forName("xp.unittest.VerboseListener");
      }
    }');
    self::$QUIET= newinstance(__CLASS__, [2, 'QUIET'], '{
      static function __static() { }
      public function getImplementation() {
        return \lang\XPClass::forName("xp.unittest.QuietListener");
      }
    }');
    self::$BAR= newinstance(__CLASS__, [3, 'BAR'], '{
      static function __static() { }
      public function getImplementation() {
        return \lang\XPClass::forName("xp.unittest.ColoredBarListener");
      }
    }');

    self::$DEFAULT= self::$COMPACT;
  }

  /**
   * Creates a listener from a given name
   *
   * @param  string $name
   * @return self
   */
  public static function named($name) {
    return Enum::valueOf(self::class, strtoupper($name));
  }

  /**
   * Creates a new listener instance
   *
   * @return  lang.XPClass
   */
  public abstract function getImplementation();

  /**
   * Creates a new listener instance
   *
   * @param   io.streams.OutputStreamWriter out
   * @return  unittest.TestListener
   */
  public function newInstance(OutputStreamWriter $out) {
    return $this->getImplementation()->newInstance($out);
  }
}