<?php

abstract class PhorgeFactEngine extends Phobject {

  private $factMap;
  private $viewer;

  final public static function loadAllEngines() {
    return id(new PhutilClassMapQuery())
      ->setAncestorClass(__CLASS__)
      ->execute();
  }

  abstract public function newFacts();

  abstract public function supportsDatapointsForObject(
    PhorgeLiskDAO $object);

  abstract public function newDatapointsForObject(PhorgeLiskDAO $object);

  final protected function getFact($key) {
    if ($this->factMap === null) {
      $facts = $this->newFacts();
      $facts = mpull($facts, null, 'getKey');
      $this->factMap = $facts;
    }

    if (!isset($this->factMap[$key])) {
      throw new Exception(
        pht(
          'Unknown fact ("%s") for engine "%s".',
          $key,
          get_class($this)));
    }

    return $this->factMap[$key];
  }

  public function setViewer(PhorgeUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function getViewer() {
    if (!$this->viewer) {
      throw new PhutilInvalidStateException('setViewer');
    }

    return $this->viewer;
  }

}
