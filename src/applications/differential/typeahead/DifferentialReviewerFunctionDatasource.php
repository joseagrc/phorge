<?php

final class DifferentialReviewerFunctionDatasource
  extends PhorgeTypeaheadCompositeDatasource {

  public function getBrowseTitle() {
    return pht('Browse Reviewers');
  }

  public function getPlaceholderText() {
    return pht('Type a user, project, package name or function...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhorgeDifferentialApplication';
  }

  public function getComponentDatasources() {
    return array(
      new PhorgeProjectOrUserFunctionDatasource(),
      new PhorgeOwnersPackageFunctionDatasource(),
      new DifferentialNoReviewersDatasource(),
    );
  }

}
