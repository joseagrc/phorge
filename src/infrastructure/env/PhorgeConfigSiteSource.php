<?php

/**
 * Optional configuration source which loads between local sources and the
 * database source.
 *
 * Subclasses of this source can read external configuration sources (like a
 * remote server).
 */
abstract class PhorgeConfigSiteSource
  extends PhorgeConfigProxySource {

  public function getPriority() {
    return 1000.0;
  }

}
