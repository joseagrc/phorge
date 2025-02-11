<?php

final class AlmanacInterfaceDatasource
  extends PhorgeTypeaheadDatasource {

  public function getBrowseTitle() {
    return pht('Browse Interfaces');
  }

  public function getPlaceholderText() {
    return pht('Type an interface name...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhorgeAlmanacApplication';
  }

  public function loadResults() {
    $viewer = $this->getViewer();
    $raw_query = $this->getRawQuery();

    $devices = id(new AlmanacDeviceQuery())
      ->setViewer($viewer)
      ->withNamePrefix($raw_query)
      ->execute();

    if ($devices) {
      $interface_query = id(new AlmanacInterfaceQuery())
        ->setViewer($viewer)
        ->withDevicePHIDs(mpull($devices, 'getPHID'))
        ->setOrder('name');

      $interfaces = $this->executeQuery($interface_query);
    } else {
      $interfaces = array();
    }

    if ($interfaces) {
      $handles = id(new PhorgeHandleQuery())
        ->setViewer($viewer)
        ->withPHIDs(mpull($interfaces, 'getPHID'))
        ->execute();
    } else {
      $handles = array();
    }

    $results = array();
    foreach ($handles as $handle) {
      if ($handle->isClosed()) {
        $closed = pht('Disabled');
      } else {
        $closed = null;
      }

      $results[] = id(new PhorgeTypeaheadResult())
        ->setName($handle->getName())
        ->setPHID($handle->getPHID())
        ->setClosed($closed);
    }

    return $results;
  }

}
