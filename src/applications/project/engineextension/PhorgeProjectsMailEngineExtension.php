<?php

final class PhorgeProjectsMailEngineExtension
  extends PhorgeMailEngineExtension {

  const EXTENSIONKEY = 'projects';

  public function supportsObject($object) {
    return ($object instanceof PhorgeProjectInterface);
  }

  public function newMailStampTemplates($object) {
    return array(
      id(new PhorgePHIDMailStamp())
        ->setKey('tag')
        ->setLabel(pht('Tagged with Project')),
    );
  }

  public function newMailStamps($object, array $xactions) {
    $editor = $this->getEditor();
    $viewer = $this->getViewer();

    $project_phids = PhorgeEdgeQuery::loadDestinationPHIDs(
      $object->getPHID(),
      PhorgeProjectObjectHasProjectEdgeType::EDGECONST);

    $this->getMailStamp('tag')
      ->setValue($project_phids);
  }

}
