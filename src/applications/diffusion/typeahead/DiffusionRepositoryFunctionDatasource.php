<?php

final class DiffusionRepositoryFunctionDatasource
  extends PhorgeTypeaheadCompositeDatasource {

  public function getBrowseTitle() {
    return pht('Browse Repositories');
  }

  public function getPlaceholderText() {
    return pht('Type a repository name or function...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhorgeDifferentialApplication';
  }

  public function getComponentDatasources() {
    return array(
      new DiffusionTaggedRepositoriesFunctionDatasource(),
      new DiffusionRepositoryDatasource(),
    );
  }

}
