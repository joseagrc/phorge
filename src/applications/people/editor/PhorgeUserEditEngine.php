<?php

final class PhorgeUserEditEngine
  extends PhorgeEditEngine {

  const ENGINECONST = 'people.user';

  public function isEngineConfigurable() {
    return false;
  }

  public function getEngineName() {
    return pht('Users');
  }

  public function getSummaryHeader() {
    return pht('Configure User Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing forms for users.');
  }

  public function getEngineApplicationClass() {
    return 'PhorgePeopleApplication';
  }

  protected function newEditableObject() {
    return new PhorgeUser();
  }

  protected function newObjectQuery() {
    return id(new PhorgePeopleQuery());
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New User');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit User: %s', $object->getUsername());
  }

  protected function getObjectEditShortText($object) {
    return $object->getMonogram();
  }

  protected function getObjectCreateShortText() {
    return pht('Create User');
  }

  protected function getObjectName() {
    return pht('User');
  }

  protected function getObjectViewURI($object) {
    return $object->getURI();
  }

  protected function getCreateNewObjectPolicy() {
    // At least for now, forbid creating new users via EditEngine. This is
    // primarily enforcing that "user.edit" can not create users via the API.
    return PhorgePolicies::POLICY_NOONE;
  }

  protected function buildCustomEditFields($object) {
    return array(
      id(new PhorgeBoolEditField())
        ->setKey('disabled')
        ->setOptions(pht('Active'), pht('Disabled'))
        ->setLabel(pht('Disabled'))
        ->setDescription(pht('Disable the user.'))
        ->setTransactionType(PhorgeUserDisableTransaction::TRANSACTIONTYPE)
        ->setIsFormField(false)
        ->setConduitDescription(pht('Disable or enable the user.'))
        ->setConduitTypeDescription(pht('True to disable the user.'))
        ->setValue($object->getIsDisabled()),
      id(new PhorgeBoolEditField())
        ->setKey('approved')
        ->setOptions(pht('Approved'), pht('Unapproved'))
        ->setLabel(pht('Approved'))
        ->setDescription(pht('Approve the user.'))
        ->setTransactionType(PhorgeUserApproveTransaction::TRANSACTIONTYPE)
        ->setIsFormField(false)
        ->setConduitDescription(pht('Approve or reject the user.'))
        ->setConduitTypeDescription(pht('True to approve the user.'))
        ->setValue($object->getIsApproved()),
    );
  }

}
