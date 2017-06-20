<?php

namespace Aircodes\AirCore\Http;

use Aircodes\AirCore\Settings;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;

class Server implements HttpServerInterface {
    protected $response;

    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null ) {

      echo 'New HTTP Connection from ', $conn->resourceId, PHP_EOL;
      $this->response = new Response(200, [
          'Content-Type' => 'text/html; charset=utf-8',
          'X-Powered-By' => \Aircodes\AirCore\Core::VERSION
      ]);

      $ipHeaders = Settings::getInstance()->get('ipHeaders', []);
      $ip = '127.0.0.1';
      foreach($ipHeaders as $header) {
        if($request->hasHeader($header)) {
          $ipParts = $request->getHeader($header);
          $ipParts = array_filter(array_map('trim', explode(',', $ipParts)));
          $ip = $ipParts[0];
          break;
        }
      }

      /**
      * TODO: remove test logic from the inside
      */
      if($conn->Session->has('name')) {
        $name = $conn->Session->get('name');
      } else {
        $conn->Session->has('name');
        $name = 'User ' . uniqid();
        $conn->Session->set('name', $name);
        $conn->Session->set('test', array(3));
      }

      $this->response->setBody( 'Hello, ' . $name . '!<br>Your IP: ' . $ip );

      /**
      */

      $this->close($conn);
    }

    public function onClose(ConnectionInterface $conn) {
      #.. on connection abort event
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
      #.. on connection error event
    }

    public function onMessage(ConnectionInterface $from, $msg) {
      #.. on connection message event
    }

    protected function close(ConnectionInterface $conn) {
      foreach($conn->Cookie->getHeaders() as $h) {
        $this->response->setHeader('Set-Cookie', $h);
      }
      $conn->send($this->response);
      $conn->close();
    }
}
