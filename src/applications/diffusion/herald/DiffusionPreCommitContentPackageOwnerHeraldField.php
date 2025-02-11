<?php

final class DiffusionPreCommitContentPackageOwnerHeraldField
  extends DiffusionPreCommitContentHeraldField {

  const FIELDCONST = 'diffusion.pre.content.package.owners';

  public function getHeraldFieldName() {
    return pht('Affected package owners');
  }

  public function getFieldGroupKey() {
    return HeraldRelatedFieldGroup::FIELDGROUPKEY;
  }

  public function getHeraldFieldValue($object) {
    $packages = $this->getAdapter()->loadAffectedPackages();
    if (!$packages) {
      return array();
    }

    $owners = PhorgeOwnersOwner::loadAllForPackages($packages);
    return mpull($owners, 'getUserPHID');
  }

  protected function getHeraldFieldStandardType() {
    return self::STANDARD_PHID_LIST;
  }

  protected function getDatasource() {
    return new PhorgeProjectOrUserDatasource();
  }

}
