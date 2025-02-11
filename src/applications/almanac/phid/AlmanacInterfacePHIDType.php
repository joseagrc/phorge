<?php

final class AlmanacInterfacePHIDType extends PhorgePHIDType {

  const TYPECONST = 'AINT';

  public function getTypeName() {
    return pht('Almanac Interface');
  }

  public function newObject() {
    return new AlmanacInterface();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhorgeAlmanacApplication';
  }

  protected function buildQueryForObjects(
    PhorgeObjectQuery $query,
    array $phids) {

    return id(new AlmanacInterfaceQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhorgeHandleQuery $query,
    array $handles,
    array $objects) {

    foreach ($handles as $phid => $handle) {
      $interface = $objects[$phid];

      $id = $interface->getID();

      $device = $interface->getDevice();
      $device_name = $device->getName();
      $address = $interface->getAddress();
      $port = $interface->getPort();
      $network = $interface->getNetwork()->getName();

      $name = pht(
        '%s:%s (%s on %s)',
        $device_name,
        $port,
        $address,
        $network);

      $handle->setObjectName(pht('Interface %d', $id));
      $handle->setName($name);

      if ($device->isDisabled()) {
        $handle->setStatus(PhorgeObjectHandle::STATUS_CLOSED);
      }
    }
  }

}
