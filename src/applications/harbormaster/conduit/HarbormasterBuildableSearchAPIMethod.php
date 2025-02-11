<?php

final class HarbormasterBuildableSearchAPIMethod
  extends PhorgeSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'harbormaster.buildable.search';
  }

  public function newSearchEngine() {
    return new HarbormasterBuildableSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Find out information about buildables.');
  }

}
