<?php

final class PhorgeConsoleContentSource
  extends PhorgeContentSource {

  const SOURCECONST = 'console';

  public function getSourceName() {
    return pht('Console');
  }

  public function getSourceDescription() {
    return pht('Content generated by CLI administrative tools.');
  }

}
