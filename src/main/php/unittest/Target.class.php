<?php namespace unittest;

class Target {

  public function __construct($name, $instance) {
    $this->name= $name;
    $this->instance= $instance;
  }
}