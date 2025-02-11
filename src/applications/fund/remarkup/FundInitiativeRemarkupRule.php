<?php

final class FundInitiativeRemarkupRule extends PhorgeObjectRemarkupRule {

  protected function getObjectNamePrefix() {
    return 'I';
  }

  protected function loadObjects(array $ids) {
    $viewer = $this->getEngine()->getConfig('viewer');

    return id(new FundInitiativeQuery())
      ->setViewer($viewer)
      ->withIDs($ids)
      ->execute();
  }

}
