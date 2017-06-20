<?php

namespace Aircodes\AirCore\Http\Cookie;

use Guzzle\Http\Message\RequestInterface;

class Storage implements StorageInterface {

  /**
  * Assoc array which store cookies
  */
  protected $storage;

  /**
  * Assoc array of changes in cookies
  * Store keys as keys and state as values
  */
  protected $changes;

  /**
  * Creating storage and setting values from Set-Cookie $request's header
  */
  public function __construct(?RequestInterface $req = null) {
    $this->changes = array();
    if($req !== null) {
      $this->storage = $req->getCookies();
    }
  }

  /**
  *  Return all cookies
  *
  *  @return array of key=>values;
  */
  public function all() : array {
    return $this->storage;
  }

  /**
  *  Return Set-Cookie: headers
  *
  *  @return array of string $headers
  */
  public function getHeaders() : array {
    return array_map('http_build_cookie', $this->changes);
  }

  /**
  * Check for value to exist
  *
  * @param string $key - Cookie name
  *
  * @return boolean State
  */
  public function has(string $key) : bool {
    return isset($this->storage[$key]);
  }
  public function __isset(string $key) : bool {
    return $this->has($key);
  }

  /**
  * Set the cookie value with $key
  *
  * @param string $key - Cookie name
  * @param mixed  $value - Cookie value
  *
  * @return StorageInterface $this
  */
  public function set(string $key, $value, ?int $expire = null, ?string $path = null, ?string $domain = null, ?int $flags = null, ?array $extras = null) : StorageInterface {
    $this->storage[$key] = $value;
    if(null === $flags) $flags = 0;
    $change = [
      'cookies' =>  [
        $key=>$value
      ],
      'extras' => array_map(function($a){return (string) $a;}, $extras ?? [])
    ];

    if(null != $expire)   $change['expires']  = (int) $expire;
    if(null != $path)     $change['path']     = $path;
    if(null != $domain)   $change['domain']   = $domain;

    if($flags & self::SECURE)          $change['secure']   = true;
    if($flags & self::HTTPONLY)        $change['httponly'] = true;
    if($flags & self::SAMESITE_LAX)    $change['samesite'] = 'Lax';
    if($flags & self::SAMESITE_STRICT) $change['samesite'] = 'Strict';

    $this->changes[$key] = $change;
    return $this;
  }
  public function __set(string $key, $value) {
    $this->set($key, $value);
  }

  /**
  * Get the cookie value by $key
  *
  * @param string $key - Cookie name
  * @param mixed  $default - default value if not exists
  *
  * @return mixed $value
  */
  public function get(string $key, $default = null) {
    return $this->storage[$key] ?? $default;
  }
  public function __get(string $key) {
    return $this->get($key);
  }

  /**
  * Remove the cookie by $key from storage
  *
  * @param string $key - Cookie name
  *
  * @return StorageInterface $this
  */
  public function del(string $key) : StorageInterface {
    $this->set($key, null, 1, '/');
  }
  public function __unset($key) {
    $this->del($key);
  }
}
