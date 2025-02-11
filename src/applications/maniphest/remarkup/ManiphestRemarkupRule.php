<?php

final class ManiphestRemarkupRule extends PhorgeObjectRemarkupRule {

  protected function getObjectNamePrefix() {
    return 'T';
  }

  protected function loadObjects(array $ids) {
    $viewer = $this->getEngine()->getConfig('viewer');

    return id(new ManiphestTaskQuery())
      ->setViewer($viewer)
      ->withIDs($ids)
      ->execute();
  }

}
