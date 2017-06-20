<?php

use Aircodes\AirCore\Core;

require __DIR__ . '/vendor/autoload.php';

try {
  $kek = 'Hello, pigs!';

  $core = new Core('178.163.42.96', 80, Core::SESSION_MEMCACHED);
  $core->with('localhost')
    ->on('/', function($req, $res) use ($kek) {
     return $kek;
    })
    ->run();
} catch (Exception $e) {
  echo $e;
}
