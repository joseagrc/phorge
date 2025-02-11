<?php

final class PhorgeDivinerApplication extends PhorgeApplication {

  public function getBaseURI() {
    return '/diviner/';
  }

  public function getIcon() {
    return 'fa-sun-o';
  }

  public function getName() {
    return pht('Diviner');
  }

  public function getShortDescription() {
    return pht('Documentation');
  }

  public function getHelpDocumentationArticles(PhorgeUser $viewer) {
    return array(
      array(
        'name' => pht('Diviner User Guide'),
        'href' => PhorgeEnv::getDoclink('Diviner User Guide'),
      ),
    );
  }

  public function getTitleGlyph() {
    return "\xE2\x97\x89";
  }

  public function getRoutes() {
    return array(
      '/diviner/' => array(
        '' => 'DivinerMainController',
        'query/((?<queryKey>[^/]+)/)?' => 'DivinerAtomListController',
        'find/' => 'DivinerFindController',
      ),
      '/book/(?P<book>[^/]+)/' => 'DivinerBookController',
      '/book/(?P<book>[^/]+)/edit/' => 'DivinerBookEditController',
      '/book/'.
        '(?P<book>[^/]+)/'.
        '(?P<type>[^/]+)/'.
        '(?:(?P<context>[^/]+)/)?'.
        '(?P<name>[^/]+)/'.
        '(?:(?P<index>\d+)/)?' => 'DivinerAtomController',
    );
  }

  public function getApplicationGroup() {
    return self::GROUP_UTILITIES;
  }

  protected function getCustomCapabilities() {
    return array(
      DivinerDefaultViewCapability::CAPABILITY => array(
        'template' => DivinerBookPHIDType::TYPECONST,
        'capability' => PhorgePolicyCapability::CAN_VIEW,
      ),
      DivinerDefaultEditCapability::CAPABILITY => array(
        'default' => PhorgePolicies::POLICY_ADMIN,
        'template' => DivinerBookPHIDType::TYPECONST,
        'capability' => PhorgePolicyCapability::CAN_EDIT,
      ),
    );
  }

  public function getRemarkupRules() {
    return array(
      new DivinerSymbolRemarkupRule(),
    );
  }

  public function getApplicationSearchDocumentTypes() {
    return array(
      DivinerAtomPHIDType::TYPECONST,
      DivinerBookPHIDType::TYPECONST,
    );
  }

}
