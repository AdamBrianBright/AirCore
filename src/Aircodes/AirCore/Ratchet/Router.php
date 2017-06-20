<?php

namespace Aircodes\AirCore\Ratchet;

use Ratchet\Http\Router as RatchetRouter;
use Ratchet\ConnectionInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;


class Router extends RatchetRouter {

  function close(ConnectionInterface $conn, $code = null, array $headers = null) {
    if(!$code) $code = 400;
    if(!$headers) $headers = [];

    $response = new Response($code, array_merge(['X-Powered-By' => \Aircodes\AirCore\Core::VERSION], $headers));
    $response->setBody("<h1>Error $code</h1>");

    $conn->send((string)$response);
    $conn->close();
  }

}
