<?php

final class PhorgeMultimeterApplication
  extends PhorgeApplication {

  public function getName() {
    return pht('Multimeter');
  }

  public function getBaseURI() {
    return '/multimeter/';
  }

  public function getIcon() {
    return 'fa-motorcycle';
  }

  public function isPrototype() {
    return true;
  }

  public function getTitleGlyph() {
    return "\xE2\x8F\xB3";
  }

  public function getApplicationGroup() {
    return self::GROUP_DEVELOPER;
  }

  public function getShortDescription() {
    return pht('Performance Sampler');
  }

  public function getRemarkupRules() {
    return array();
  }

  public function getRoutes() {
    return array(
      '/multimeter/' => array(
        '' => 'MultimeterSampleController',
      ),
    );
  }

  public function getHelpDocumentationArticles(PhorgeUser $viewer) {
    return array(
      array(
        'name' => pht('Multimeter User Guide'),
        'href' => PhorgeEnv::getDoclink('Multimeter User Guide'),
      ),
    );
  }

}
