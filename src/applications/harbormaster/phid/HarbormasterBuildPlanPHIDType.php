<?php

final class HarbormasterBuildPlanPHIDType extends PhorgePHIDType {

  const TYPECONST = 'HMCP';

  public function getTypeName() {
    return pht('Build Plan');
  }

  public function getTypeIcon() {
    return 'fa-cubes';
  }

  public function newObject() {
    return new HarbormasterBuildPlan();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhorgeHarbormasterApplication';
  }

  protected function buildQueryForObjects(
    PhorgeObjectQuery $query,
    array $phids) {

    return id(new HarbormasterBuildPlanQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhorgeHandleQuery $query,
    array $handles,
    array $objects) {

    foreach ($handles as $phid => $handle) {
      $build_plan = $objects[$phid];
      $id = $build_plan->getID();
      $handles[$phid]->setName(pht('Plan %d %s', $id, $build_plan->getName()));
      $handles[$phid]->setURI('/harbormaster/plan/'.$id.'/');
    }
  }

}
