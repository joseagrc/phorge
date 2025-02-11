<?php

final class PassphraseRemarkupRule extends PhorgeObjectRemarkupRule {

  protected function getObjectNamePrefix() {
    return 'K';
  }

  protected function loadObjects(array $ids) {
    $viewer = $this->getEngine()->getConfig('viewer');

    return id(new PassphraseCredentialQuery())
      ->setViewer($viewer)
      ->withIDs($ids)
      ->execute();

  }
}
