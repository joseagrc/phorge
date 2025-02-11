<?php

final class PhorgeRobotsResourceController
  extends PhorgeRobotsController {

  protected function newRobotsRules() {
    $out = array();

    // See T13636. Prevent indexing of any content on resource domains.

    $out[] = 'User-Agent: *';
    $out[] = 'Disallow: /';
    $out[] = 'Crawl-delay: 1';

    return $out;
  }

}
