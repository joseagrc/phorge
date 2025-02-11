<?php

final class PhorgeDashboardRemarkupRule
  extends PhorgeObjectRemarkupRule {

  const KEY_PARENT_PANEL_PHIDS = 'dashboard.parentPanelPHIDs';

  protected function getObjectNamePrefix() {
    return 'W';
  }

  protected function loadObjects(array $ids) {
    $viewer = $this->getEngine()->getConfig('viewer');

    return id(new PhorgeDashboardPanelQuery())
      ->setViewer($viewer)
      ->withIDs($ids)
      ->execute();
  }

  protected function renderObjectEmbed(
    $object,
    PhorgeObjectHandle $handle,
    $options) {

    $engine = $this->getEngine();
    $viewer = $engine->getConfig('viewer');

    $parent_key = self::KEY_PARENT_PANEL_PHIDS;
    $parent_phids = $engine->getConfig($parent_key, array());

    return id(new PhorgeDashboardPanelRenderingEngine())
      ->setViewer($viewer)
      ->setPanel($object)
      ->setPanelPHID($object->getPHID())
      ->setParentPanelPHIDs($parent_phids)
      ->renderPanel();

  }
}
