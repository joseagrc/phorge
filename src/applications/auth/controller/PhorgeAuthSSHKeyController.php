<?php

abstract class PhorgeAuthSSHKeyController
  extends PhorgeAuthController {

  private $keyObject;

  public function setSSHKeyObject(PhorgeSSHPublicKeyInterface $object) {
    $this->keyObject = $object;
    return $this;
  }

  public function getSSHKeyObject() {
    return $this->keyObject;
  }

  protected function loadSSHKeyObject($object_phid, $need_edit) {
    $viewer = $this->getViewer();

    $query = id(new PhorgeObjectQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($object_phid));

    if ($need_edit) {
      $query->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ));
    }

    $object = $query->executeOne();

    if (!$object) {
      return null;
    }

    // If this kind of object can't have SSH keys, don't let the viewer
    // add them.
    if (!($object instanceof PhorgeSSHPublicKeyInterface)) {
      return null;
    }

    $this->keyObject = $object;

    return $object;
  }

  protected function newKeyForObjectPHID($object_phid) {
    $viewer = $this->getViewer();

    $object = $this->loadSSHKeyObject($object_phid, true);
    if (!$object) {
      return null;
    }

    return PhorgeAuthSSHKey::initializeNewSSHKey($viewer, $object);
  }

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();
    $viewer = $this->getViewer();

    $key_object = $this->getSSHKeyObject();
    if ($key_object) {
      $object_phid = $key_object->getPHID();
      $handles = $viewer->loadHandles(array($object_phid));
      $handle = $handles[$object_phid];

      $uri = $key_object->getSSHPublicKeyManagementURI($viewer);

      $crumbs->addTextCrumb($handle->getObjectName(), $uri);
    }

    return $crumbs;
  }

}
