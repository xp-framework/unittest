<?php namespace unittest;

class Warnings extends \lang\XPException {
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
      $s.= '  '.$warning[2]."\n";
    }
    return $s.'}';
  }
}