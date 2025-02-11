<?php

final class PhorgeFeedApplication extends PhorgeApplication {

  public function getBaseURI() {
    return '/feed/';
  }

  public function getName() {
    return pht('Feed');
  }

  public function getShortDescription() {
    return pht('Review Recent Activity');
  }

  public function getIcon() {
    return 'fa-newspaper-o';
  }

  public function getApplicationGroup() {
    return self::GROUP_UTILITIES;
  }

  public function canUninstall() {
    return false;
  }

  public function getRoutes() {
    return array(
      '/feed/' => array(
        '(?P<id>\d+)/' => 'PhorgeFeedDetailController',
        '(?:query/(?P<queryKey>[^/]+)/)?' => 'PhorgeFeedListController',
        'transactions/' => array(
          $this->getQueryRoutePattern()
            => 'PhorgeFeedTransactionListController',
        ),
      ),
    );
  }

}
