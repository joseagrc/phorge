<?php

final class HarbormasterRemarkupRule extends PhorgeObjectRemarkupRule {

  protected function getObjectNamePrefix() {
    return 'B';
  }

  protected function loadObjects(array $ids) {
    $viewer = $this->getEngine()->getConfig('viewer');
    return id(new HarbormasterBuildableQuery())
      ->setViewer($viewer)
      ->withIDs($ids)
      ->execute();
  }

}
