<?php

final class NuanceQueuePHIDType extends PhorgePHIDType {

  const TYPECONST = 'NUAQ';

  public function getTypeName() {
    return pht('Queue');
  }

  public function newObject() {
    return new NuanceQueue();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhorgeNuanceApplication';
  }

  protected function buildQueryForObjects(
    PhorgeObjectQuery $query,
    array $phids) {

    return id(new NuanceQueueQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhorgeHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $queue = $objects[$phid];

      $handle->setName($queue->getName());
      $handle->setURI($queue->getURI());
    }
  }

}
