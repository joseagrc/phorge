<?php

abstract class HarbormasterConduitAPIMethod extends ConduitAPIMethod {

  final public function getApplication() {
    return PhorgeApplication::getByClass(
      'PhorgeHarbormasterApplication');
  }

  protected function returnArtifactList(array $artifacts) {
    $list = array();

    foreach ($artifacts as $artifact) {
      $list[] = array(
        'phid' => $artifact->getPHID(),
      );
    }

    return $list;
  }

}
