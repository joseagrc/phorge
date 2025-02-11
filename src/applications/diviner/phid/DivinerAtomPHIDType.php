<?php

final class DivinerAtomPHIDType extends PhorgePHIDType {

  const TYPECONST = 'ATOM';

  public function getTypeName() {
    return pht('Diviner Atom');
  }

  public function newObject() {
    return new DivinerLiveSymbol();
  }

  public function getTypeIcon() {
    return 'fa-cube';
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhorgeDivinerApplication';
  }

  protected function buildQueryForObjects(
    PhorgeObjectQuery $query,
    array $phids) {

    return id(new DivinerAtomQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhorgeHandleQuery $query,
    array $handles,
    array $objects) {

    foreach ($handles as $phid => $handle) {
      $atom = $objects[$phid];

      $book = $atom->getBook()->getName();
      $name = $atom->getName();
      $type = $atom->getType();

      $handle
        ->setName($atom->getName())
        ->setTitle($atom->getTitle())
        ->setURI("/book/{$book}/{$type}/{$name}/")
        ->setStatus($atom->getGraphHash()
          ? PhorgeObjectHandle::STATUS_OPEN
          : PhorgeObjectHandle::STATUS_CLOSED);
    }
  }

}
