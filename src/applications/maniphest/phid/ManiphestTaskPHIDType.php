<?php

final class ManiphestTaskPHIDType extends PhorgePHIDType {

  const TYPECONST = 'TASK';

  public function getTypeName() {
    return pht('Maniphest Task');
  }

  public function newObject() {
    return new ManiphestTask();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhorgeManiphestApplication';
  }

  protected function buildQueryForObjects(
    PhorgeObjectQuery $query,
    array $phids) {

    return id(new ManiphestTaskQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhorgeHandleQuery $query,
    array $handles,
    array $objects) {

    foreach ($handles as $phid => $handle) {
      $task = $objects[$phid];
      $id = $task->getID();
      $title = $task->getTitle();

      $handle->setName("T{$id}");
      $handle->setFullName("T{$id}: {$title}");
      $handle->setURI("/T{$id}");

      if ($task->isClosed()) {
        $handle->setStatus(PhorgeObjectHandle::STATUS_CLOSED);
      }
    }
  }

  public function canLoadNamedObject($name) {
    return preg_match('/^T\d*[1-9]\d*$/i', $name);
  }

  public function loadNamedObjects(
    PhorgeObjectQuery $query,
    array $names) {

    $id_map = array();
    foreach ($names as $name) {
      $id = (int)substr($name, 1);
      $id_map[$id][] = $name;
    }

    $objects = id(new ManiphestTaskQuery())
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
