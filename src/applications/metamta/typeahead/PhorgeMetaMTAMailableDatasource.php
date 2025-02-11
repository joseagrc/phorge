<?php

final class PhorgeMetaMTAMailableDatasource
  extends PhorgeTypeaheadCompositeDatasource {

  public function getBrowseTitle() {
    return pht('Browse Subscribers');
  }

  public function getPlaceholderText() {
    return pht('Type a user, project, package, or mailing list name...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhorgeMetaMTAApplication';
  }

  public function getComponentDatasources() {
    return array(
      new PhorgePeopleDatasource(),
      new PhorgeProjectDatasource(),
      new PhorgeOwnersPackageDatasource(),
    );
  }

}
