<?php

namespace Aircodes\AirCore\Ratchet;

use Ratchet\App as RatchetApp;

use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as Reactor;
use Ratchet\Http\HttpServerInterface;
use Ratchet\Http\OriginCheck;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\Server\IoServer;
use Ratchet\Server\FlashPolicy;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class App extends RatchetApp {
  protected $policy;


  public function __construct($httpHost = 'localhost', $port = 8080, $address = '127.0.0.1', LoopInterface $loop = null) {

    if (extension_loaded('xdebug')) {
        trigger_error('XDebug extension detected. Remember to disable this if performance testing or going live!', E_USER_WARNING);
    }

    if (3 !== strlen('✓')) {
        throw new \DomainException('Bad encoding, length of unicode character ✓ should be 3. Ensure charset UTF-8 and check ini val mbstring.func_autoload');
    }

    if (null === $loop) {
        $loop = LoopFactory::create();
    }

    $this->httpHost = $httpHost;
    $this->port = $port;

    $socket = new Reactor($loop);
    $socket->listen($port, $address);

    $this->routes  = new RouteCollection;
    $router = new Router(new UrlMatcher($this->routes, new RequestContext));
    $this->_server = new IoServer(new HttpServer($router), $socket, $loop);

    $policy = new FlashPolicy;
    $policy->addAllowedAccess($httpHost, 80);
    $policy->addAllowedAccess($httpHost, $port);
    $this->policy = $policy;

    $flashSock = new Reactor($loop);
    $this->flashServer = new IoServer($policy, $flashSock);

    if (80 == $port) {
        $flashSock->listen(843, '0.0.0.0');
    } else {
        $flashSock->listen(8843);
    }

  }

  public function addAllowedAccess(string $httpHost, int $port) : void {
    $this->policy->addAllowedAccess($httpHost, $port);
  }

}
