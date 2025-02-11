<?php

final class PhorgeGuideApplication extends PhorgeApplication {

  public function getBaseURI() {
    return '/guides/';
  }

  public function getName() {
    return pht('Guides');
  }

  public function getShortDescription() {
    return pht('Short Tutorials');
  }

  public function getIcon() {
    return 'fa-map-o';
  }

  public function getApplicationGroup() {
    return self::GROUP_UTILITIES;
  }

  public function getRoutes() {
    return array(
      '/guides/' => array(
        '' => 'PhorgeGuideModuleController',
        '(?P<module>[^/]+)/' => 'PhorgeGuideModuleController',
       ),
    );
  }

}
