<?php

final class PhorgeOwnersPackagePHIDType extends PhorgePHIDType {

  const TYPECONST = 'OPKG';

  public function getTypeName() {
    return pht('Owners Package');
  }

  public function getTypeIcon() {
    return 'fa-shopping-bag';
  }

  public function newObject() {
    return new PhorgeOwnersPackage();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhorgeOwnersApplication';
  }

  protected function buildQueryForObjects(
    PhorgeObjectQuery $query,
    array $phids) {

    return id(new PhorgeOwnersPackageQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhorgeHandleQuery $query,
    array $handles,
    array $objects) {

    foreach ($handles as $phid => $handle) {
      $package = $objects[$phid];

      $monogram = $package->getMonogram();
      $name = $package->getName();
      $id = $package->getID();
      $uri = $package->getURI();

      $handle
        ->setName($monogram)
        ->setFullName("{$monogram}: {$name}")
        ->setCommandLineObjectName("{$monogram} {$name}")
        ->setMailStampName($monogram)
        ->setURI($uri);

      if ($package->isArchived()) {
        $handle->setStatus(PhorgeObjectHandle::STATUS_CLOSED);
      }
    }
  }

  public function canLoadNamedObject($name) {
    return preg_match('/^O\d*[1-9]\d*$/i', $name);
  }

  public function loadNamedObjects(
    PhorgeObjectQuery $query,
    array $names) {

    $id_map = array();
    foreach ($names as $name) {
      $id = (int)substr($name, 1);
      $id_map[$id][] = $name;
    }

    $objects = id(new PhorgeOwnersPackageQuery())
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
