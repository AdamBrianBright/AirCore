<?php

namespace Aircodes\AirCore\Http\Cookie;

use Guzzle\Http\Message\RequestInterface;

interface StorageInterface {

  const SECURE          = 1 << 0;
  const HTTPONLY        = 1 << 1;
  const SAMESITE_LAX    = 1 << 2;
  const SAMESITE_STRICT = 1 << 3;

  /**
  * Creating storage and setting values from Set-Cookie $request's header
  */
  public function __construct(?RequestInterface $req = null);

  /**
  *  Return all cookies
  *
  *  @return array of key=>values;
  */
  public function all() : array;

  /**
  *  Return Set-Cookie: headers
  *
  *  @return array of string $headers
  */
  public function getHeaders() : array;

  /**
  * Check for value to exist
  *
  * @param string $key - Cookie name
  *
  * @return boolean State
  */
  public function has(string $key) : bool;

  /**
  * Set the cookie value with $key
  *
  * @param string $key - Cookie name
  * @param mixed  $value - Cookie value
  *
  * @return CookieStorageInterface $this
  */
  public function set(string $key, $value, ?int $expire = null, ?string $path = null, ?string $domain = null, ?int $flags = null, ?array $extras = null) : StorageInterface;

  /**
  * Get the cookie value by $key
  *
  * @param string $key - Cookie name
  * @param mixed  $default - default value if not exists
  *
  * @return mixed $value
  */
  public function get(string $key, $default) /*: mixed*/;

  /**
  * Remove the cookie by $key from storage
  *
  * @param string $key - Cookie name
  *
  * @return CookieStorageInterface $this
  */
  public function del(string $key) : StorageInterface;

}
