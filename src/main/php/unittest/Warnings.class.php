<?php namespace unittest;

class Warnings extends \lang\XPException {
  const MESSAGE = 2;

  private $list;

  /** @param string[] $lit */
  public function __construct($list) {
    $this->list= $list;    
    parent::__construct(sizeof($list).' warning(s) raised');
  }

  /** @return var[] */
  public function first() { return $this->list[0]; }

  /** @return var[][] */
  public function all() { return $this->list; }

  /** @return string */
  public function compoundMessage() {
    $s= nameof($this).'('.sizeof($this->list).')';
    if (empty($this->list)) return $s;

    $s.= "@{\n";
    foreach ($this->list as $warning) {
      $s.= '  '.$warning[self::MESSAGE]."\n";
    }
    return $s.'}';
  }

  /** @return void */
  public static function clear() { \xp::gc(); }

  /**
   * Returns all errors in registry
   *
   * @return var[][]
   */
  public static function raised() {
    $w= [];
    foreach (\xp::$errors as $file => $lookup) {
      foreach ($lookup as $line => $messages) {
        foreach ($messages as $message => $detail) {
          $w[]= [$file, $line, sprintf(
            '"%s" in %s::%s() (%s, line %d, occured %s)',
            $message,
            $detail['class'],
            $detail['method'],
            basename($file),
            $line,
            1 === $detail['cnt'] ? 'once' : $detail['cnt'].' times'
          )];
        }
      }
    }
    return $w;
  }
}