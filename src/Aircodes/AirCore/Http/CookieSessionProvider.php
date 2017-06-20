<?php

namespace Aircodes\AirCore\Http;

use Aircodes\AirCore\Http\Session\Serialize\PhpHandler;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\Handler as SymfonySessionHandler;
use Ratchet\Session\Serialize\HandlerInterface;

class CookieSessionProvider implements HttpServerInterface {

  protected $_app;
  protected $_handler;
  protected $_serializer;
  protected $_session;

  public function __construct(HttpServerInterface $app, \SessionHandlerInterface $handler, array $options = array(), HandlerInterface $serializer = null) {
    $this->_app = $app;
    $this->_handler = $handler;

    ini_set('session.auto_start', 0);
    ini_set('session.cache_limiter', '');
    ini_set('session.use_cookies', 0);

    $this->setOptions($options);

    if (null === $serializer) {
      $serializer = new PhpHandler;
    }

    $this->_serializer = $serializer;
    $this->_session = ini_get('session.name');
  }

  public function onOpen( ConnectionInterface $conn, RequestInterface $request = null ) {

    $conn->Cookie  = new Cookie\Storage($request);

    if(null === $request || null === ($sessid = $conn->Cookie->get($this->_session))) {
      // new session if session not found
      $sessid = $this->generateUniqueUserSessionId($conn);
      $conn->Cookie->set($this->_session, $sessid, time() + 31536000, '/', null, Cookie\Storage::HTTPONLY);
      // $request->setHeader('Set-Cookie', $this->_session . '=' . $sessid);
    }

    $conn->Session = new SymfonySession(new Session\Storage($this->_handler, $sessid, $this->_serializer));

    if (ini_get('session.auto_start')) {
      $conn->Session->start();
    }

    return $this->_app->onOpen($conn, $request);
  }

  public function onClose( ConnectionInterface $conn ) {
    $return = $this->_app->onClose($conn);
    $conn->Session->Save();
    return $return;
  }

  public function onError( ConnectionInterface $conn, \Exception $e ) {
    return $this->_app->onError($conn, $e);
  }

  public function onMessage( ConnectionInterface $from, $msg ) {
    return $this->_app->onMessage($conn);
  }

  protected function setOptions(array $options) {
    $all = array(
      'auto_start', 'cache_limiter', 'cookie_domain', 'cookie_httponly',
      'cookie_lifetime', 'cookie_path', 'cookie_secure',
      'entropy_file', 'entropy_length', 'gc_divisor',
      'gc_maxlifetime', 'gc_probability', 'hash_bits_per_character',
      'hash_function', 'name', 'referer_check',
      'serialize_handler', 'use_cookies',
      'use_only_cookies', 'use_trans_sid', 'upload_progress.enabled',
      'upload_progress.cleanup', 'upload_progress.prefix', 'upload_progress.name',
      'upload_progress.freq', 'upload_progress.min-freq', 'url_rewriter.tags'
    );

    foreach ($all as $key) {
      if (!array_key_exists($key, $options)) {
        $options[$key] = ini_get("session.{$key}");
      } else {
        ini_set("session.{$key}", $options[$key]);
      }
    }

    return $options;
  }

  protected function toClassCase($langDef) {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $langDef)));
  }

  protected function generateUniqueUserSessionId( ConnectionInterface $conn ) : string {
    // Each connection has it's own resourceId and run time
    // Each simultaneous connections resourceId unique
    $uusi = md5(base64_encode(round(microtime(true)))) . md5($conn->resourceId);
    return $uusi;
  }
}
