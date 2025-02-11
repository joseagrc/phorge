<?php

final class DrydockBlueprintSearchConduitAPIMethod
  extends PhorgeSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'drydock.blueprint.search';
  }

  public function newSearchEngine() {
    return new DrydockBlueprintSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Retrieve information about Drydock blueprints.');
  }

}
