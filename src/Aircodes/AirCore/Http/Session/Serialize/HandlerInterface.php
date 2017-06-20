<?php
namespace Aircodes\AirCore\Http\Session\Serialize;

interface HandlerInterface {
  function serialize(array $data) : string;
  function unserialize(string $raw) : array;
}
