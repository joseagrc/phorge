<?php

final class PhorgeAuthFactorProviderEditEngine
  extends PhorgeEditEngine {

  private $providerFactor;

  const ENGINECONST = 'auth.factor.provider';

  public function isEngineConfigurable() {
    return false;
  }

  public function getEngineName() {
    return pht('MFA Providers');
  }

  public function getSummaryHeader() {
    return pht('Edit MFA Providers');
  }

  public function getSummaryText() {
    return pht('This engine is used to edit MFA providers.');
  }

  public function getEngineApplicationClass() {
    return 'PhorgeAuthApplication';
  }

  public function setProviderFactor(PhorgeAuthFactor $factor) {
    $this->providerFactor = $factor;
    return $this;
  }

  public function getProviderFactor() {
    return $this->providerFactor;
  }

  protected function newEditableObject() {
    $factor = $this->getProviderFactor();
    if ($factor) {
      $provider = PhorgeAuthFactorProvider::initializeNewProvider($factor);
    } else {
      $provider = new PhorgeAuthFactorProvider();
    }

    return $provider;
  }

  protected function newObjectQuery() {
    return new PhorgeAuthFactorProviderQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create MFA Provider');
  }

  protected function getObjectCreateButtonText($object) {
    return pht('Create MFA Provider');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit MFA Provider');
  }

  protected function getObjectEditShortText($object) {
    return $object->getObjectName();
  }

  protected function getObjectCreateShortText() {
    return pht('Create MFA Provider');
  }

  protected function getObjectName() {
    return pht('MFA Provider');
  }

  protected function getEditorURI() {
    return '/auth/mfa/edit/';
  }

  protected function getObjectCreateCancelURI($object) {
    return '/auth/mfa/';
  }

  protected function getObjectViewURI($object) {
    return $object->getURI();
  }

  protected function getCreateNewObjectPolicy() {
    return $this->getApplication()->getPolicy(
      AuthManageProvidersCapability::CAPABILITY);
  }

  protected function buildCustomEditFields($object) {
    $factor = $object->getFactor();
    $factor_name = $factor->getFactorName();

    $status_map = PhorgeAuthFactorProviderStatus::getMap();

    $fields = array(
      id(new PhorgeStaticEditField())
        ->setKey('displayType')
        ->setLabel(pht('Factor Type'))
        ->setDescription(pht('Type of the MFA provider.'))
        ->setValue($factor_name),
      id(new PhorgeTextEditField())
        ->setKey('name')
        ->setTransactionType(
          PhorgeAuthFactorProviderNameTransaction::TRANSACTIONTYPE)
        ->setLabel(pht('Name'))
        ->setDescription(pht('Display name for the MFA provider.'))
        ->setValue($object->getName())
        ->setPlaceholder($factor_name),
      id(new PhorgeSelectEditField())
        ->setKey('status')
        ->setTransactionType(
          PhorgeAuthFactorProviderStatusTransaction::TRANSACTIONTYPE)
        ->setLabel(pht('Status'))
        ->setDescription(pht('Status of the MFA provider.'))
        ->setValue($object->getStatus())
        ->setOptions($status_map),
    );

    $factor_fields = $factor->newEditEngineFields($this, $object);
    foreach ($factor_fields as $field) {
      $fields[] = $field;
    }

    return $fields;
  }

}
