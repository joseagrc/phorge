<?php

final class PhorgeCountdownCountdownPHIDType extends PhorgePHIDType {

  const TYPECONST = 'CDWN';

  public function getTypeName() {
    return pht('Countdown');
  }

  public function newObject() {
    return new PhorgeCountdown();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhorgeCountdownApplication';
  }

  protected function buildQueryForObjects(
    PhorgeObjectQuery $query,
    array $phids) {

    return id(new PhorgeCountdownQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhorgeHandleQuery $query,
    array $handles,
    array $objects) {

    foreach ($handles as $phid => $handle) {
      $countdown = $objects[$phid];

      $name = $countdown->getTitle();
      $id = $countdown->getID();

      $handle->setName($countdown->getMonogram());
      $handle->setFullName(pht('%s: %s', $countdown->getMonogram(), $name));
      $handle->setURI($countdown->getURI());
    }
  }

  public function canLoadNamedObject($name) {
    return preg_match('/^C\d*[1-9]\d*$/i', $name);
  }

  public function loadNamedObjects(
    PhorgeObjectQuery $query,
    array $names) {

    $id_map = array();
    foreach ($names as $name) {
      $id = (int)substr($name, 1);
      $id_map[$id][] = $name;
    }

    $objects = id(new PhorgeCountdownQuery())
      ->setViewer($query->getViewer())
      ->withIDs(array_keys($id_map))
      ->execute();

    $results = array();
    foreach ($objects as $id => $object) {
      foreach (idx($id_map, $id, array()) as $name) {
        $results[$name] = $object;
      }
    }

    return $results;
  }

}
