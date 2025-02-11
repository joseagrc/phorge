<?php

final class PhorgeOAuthServerEditEngine
  extends PhorgeEditEngine {

  const ENGINECONST = 'oauthserver.application';

  public function isEngineConfigurable() {
    return false;
  }

  public function getEngineName() {
    return pht('OAuth Applications');
  }

  public function getSummaryHeader() {
    return pht('Edit OAuth Applications');
  }

  public function getSummaryText() {
    return pht('This engine manages OAuth client applications.');
  }

  public function getEngineApplicationClass() {
    return 'PhorgeOAuthServerApplication';
  }

  protected function newEditableObject() {
    return PhorgeOAuthServerClient::initializeNewClient(
      $this->getViewer());
  }

  protected function newObjectQuery() {
    return id(new PhorgeOAuthServerClientQuery());
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create OAuth Server');
  }

  protected function getObjectCreateButtonText($object) {
    return pht('Create OAuth Server');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit OAuth Server: %s', $object->getName());
  }

  protected function getObjectEditShortText($object) {
    return pht('Edit OAuth Server');
  }

  protected function getObjectCreateShortText() {
    return pht('Create OAuth Server');
  }

  protected function getObjectName() {
    return pht('OAuth Server');
  }

  protected function getObjectViewURI($object) {
    return $object->getViewURI();
  }

  protected function getCreateNewObjectPolicy() {
    return $this->getApplication()->getPolicy(
      PhorgeOAuthServerCreateClientsCapability::CAPABILITY);
  }

  protected function buildCustomEditFields($object) {
    return array(
      id(new PhorgeTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setIsRequired(true)
        ->setTransactionType(PhorgeOAuthServerTransaction::TYPE_NAME)
        ->setDescription(pht('The name of the OAuth application.'))
        ->setConduitDescription(pht('Rename the application.'))
        ->setConduitTypeDescription(pht('New application name.'))
        ->setValue($object->getName()),
      id(new PhorgeTextEditField())
        ->setKey('redirectURI')
        ->setLabel(pht('Redirect URI'))
        ->setIsRequired(true)
        ->setTransactionType(
          PhorgeOAuthServerTransaction::TYPE_REDIRECT_URI)
        ->setDescription(
          pht('The redirect URI for OAuth handshakes.'))
        ->setConduitDescription(
          pht(
            'Change where this application redirects users to during OAuth '.
            'handshakes.'))
        ->setConduitTypeDescription(
          pht(
            'New OAuth application redirect URI.'))
        ->setValue($object->getRedirectURI()),
    );
  }

}
