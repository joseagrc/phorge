<?php

final class DifferentialDiffAuthorProjectsHeraldField
  extends DifferentialDiffHeraldField {

  const FIELDCONST = 'differential.diff.author.projects';

  public function getHeraldFieldName() {
    return pht("Author's projects");
  }

  public function getHeraldFieldValue($object) {
    $viewer = PhorgeUser::getOmnipotentUser();

    $projects = id(new PhorgeProjectQuery())
      ->setViewer($viewer)
      ->withMemberPHIDs(array($object->getAuthorPHID()))
      ->execute();

    return mpull($projects, 'getPHID');
  }

  protected function getHeraldFieldStandardType() {
    return self::STANDARD_PHID_LIST;
  }

  protected function getDatasource() {
    return new PhorgeProjectDatasource();
  }

}
