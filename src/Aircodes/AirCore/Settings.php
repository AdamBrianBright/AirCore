<?php

namespace Aircodes\AirCore;

// Settings class.
// Values available from everywhere by static links

class Settings {

  protected $data = [];

  protected static $instance = null;

  protected function __construct() {

  }

  public static function getInstance() : Settings {
    if(null === self::$instance) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  public function set(string $key, $value) : Settings {
    $this->data[$key] = $value;
    return $this;
  }

  public function get(string $key, $default = null)  {
    return $this->data[$key] ?? $default;
  }

  public function has(string $key) : bool {
    return isset($this->data[$key]);
  }

  public function del(string $key) : Settings {
    unset($this->data[$key]);
    return $this;
  }

}
