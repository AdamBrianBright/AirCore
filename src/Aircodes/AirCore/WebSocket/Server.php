<?php

namespace Aircodes\AirCore\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;
use Exception;

class Server implements MessageComponentInterface {

    public function __construct(LoopInterface $loop) {
      $this->clients = new \SplObjectStorage;
      $this->loop = $loop;
    }

    public function setTimeout($c, $i) {
      $this->loop->addTimer($i, $c);
    }

    public function setInterval($c, $i) {
      $this->loop->addPeriodicTimer($i, $c);
    }

    public function onOpen(ConnectionInterface $conn) {
      $this->clients->attach($conn);
      $this->open($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
      echo "New message from {$from->resourceId} : {$msg}\n";
      $msg = json_decode($msg, true);
      if(!isset($msg['a']) || empty($msg['a'])) {
        echo "Can't get action\n";
        return;
      }
      if(!method_exists($this, 'on_' . $msg['a'])) {
        echo "Method not exists on_{$msg['a']}\n";
        return;
      }
      $this->{'on_' . $msg['a']}( $from, $msg['b'] ?? [] );
    }

    public function sendAll($type, $data) {
      foreach ($this->clients as $client) {
        $this->send($client, $type, $data);
      }
    }

    public function sendOthers($type, $data, $from) {
      foreach ($this->clients as $client) {
        if($from !== $client) {
          $this->send($client, $type, $data);
        }
      }
    }

    public function send($to, $type, $data) {
      $to->send(json_encode([
        'a' => $type,
        'b' => $data
      ]));
    }

    public function onClose(ConnectionInterface $conn) {
      $this->close($conn);
      $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
      $this->error($conn, $e);
      $conn->close();
    }

    public function open(ConnectionInterface $conn) {
      echo "New connection! ({$conn->resourceId})\n";
    }
    public function close(ConnectionInterface $conn) {
      echo "Connection {$conn->resourceId} has disconnected\n";
    }
    public function error(ConnectionInterface $conn, \Exception $e) {
      echo "An error has occurred: {$e->getMessage()}\n";
    }
}
