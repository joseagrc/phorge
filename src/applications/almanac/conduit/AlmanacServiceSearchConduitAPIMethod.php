<?php

final class AlmanacServiceSearchConduitAPIMethod
  extends PhorgeSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'almanac.service.search';
  }

  public function newSearchEngine() {
    return new AlmanacServiceSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Read information about Almanac services.');
  }

}
