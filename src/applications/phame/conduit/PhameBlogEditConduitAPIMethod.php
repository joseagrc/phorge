<?php

final class PhameBlogEditConduitAPIMethod
  extends PhorgeEditEngineAPIMethod {

  public function getAPIMethodName() {
    return 'phame.blog.edit';
  }

  public function newEditEngine() {
    return new PhameBlogEditEngine();
  }

  public function getMethodSummary() {
    return pht('Create or edit blogs in Phame.');
  }

}
