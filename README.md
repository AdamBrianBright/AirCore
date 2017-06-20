## AirCore
##### WebSocket & HTTP server wrapper

Based on [Ratchet](socketo.me)

### WARNING
Project is abandoned. New Project 'Fancy' will be able soon.


#### Get started
```PHP
<?php
require __DIR__ . '/vendor/autoload.php';
use Aircodes\AirCore\Core;

$Core = new Core('127.0.0.1', 1337, Core::SESSION_MEMCACHED | Core::IP_FORWARDED);
$Core->with('localhost')
->on('/', function($req, $res) : void {
$res->send('<h1>Hello world!</h1>');
});

$Core->run();
```
