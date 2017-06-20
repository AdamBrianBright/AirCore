<?php

namespace Aircodes\AirCore\Http\Session;
use Ratchet\Session\Storage\VirtualSessionStorage;

class Storage extends VirtualSessionStorage {

  public function __construct(\SessionHandlerInterface $handler, $sessionId, Serialize\HandlerInterface $serializer) {
    $this->setSaveHandler($handler);
    $this->saveHandler->setId($sessionId);
    $this->_serializer = $serializer;
    $this->setMetadataBag(null);
  }

  public function start() {
    if ($this->started && !$this->closed) {
      return true;
    }
    $shId        = $this->saveHandler->getId();
    $rawData     = $this->saveHandler->read($shId);
    $sessionData = $this->_serializer->unserialize($rawData);
    $this->loadSession($sessionData);

    if (!$this->saveHandler->isWrapper() && !$this->saveHandler->isSessionHandlerInterface()) {
      $this->saveHandler->setActive(false);
    }
    return true;
  }

  public function save(string $bag = null) {
    if($bag === null) $bag = 'attributes';

    $sessId = $this->saveHandler->getId();
    $data = $this->getBag($bag)->all();
    $rawData = $this->_serializer->serialize($data);
    try {
      $this->saveHandler->write($sessId, $rawData);
    } catch (\Exception $e) {
      echo "Error occured while writing session: ", $e->getMessage(), PHP_EOL;
    }
    if (!$this->saveHandler->isWrapper() && !$this->getSaveHandler()->isSessionHandlerInterface()) {
      $this->saveHandler->setActive(false);
    }
    $this->closed = true;
  }

  protected function loadSession(array &$session = null) {
    $bag = &$this->bags['attributes'];
    foreach($session as $key=>$value) {
      $bag->set($key, $value);
    }

    $this->started = true;
    $this->closed = false;
  }

}
