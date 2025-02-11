<?php

final class PhortuneMerchant extends PhortuneDAO
  implements
    PhorgeApplicationTransactionInterface,
    PhorgePolicyInterface {

  protected $name;
  protected $description;
  protected $contactInfo;
  protected $invoiceEmail;
  protected $invoiceFooter;
  protected $profileImagePHID;

  private $memberPHIDs = self::ATTACHABLE;
  private $profileImageFile = self::ATTACHABLE;

  public static function initializeNewMerchant(PhorgeUser $actor) {
    return id(new PhortuneMerchant())
      ->attachMemberPHIDs(array())
      ->setContactInfo('')
      ->setInvoiceEmail('')
      ->setInvoiceFooter('');
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'name' => 'text255',
        'description' => 'text',
        'contactInfo' => 'text',
        'invoiceEmail' => 'text255',
        'invoiceFooter' => 'text',
        'profileImagePHID' => 'phid?',
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhorgePHID::generateNewPHID(
      PhortuneMerchantPHIDType::TYPECONST);
  }

  public function getMemberPHIDs() {
    return $this->assertAttached($this->memberPHIDs);
  }

  public function attachMemberPHIDs(array $member_phids) {
    $this->memberPHIDs = $member_phids;
    return $this;
  }

  public function getURI() {
    return '/phortune/merchant/'.$this->getID().'/';
  }

  public function getProfileImageURI() {
    return $this->getProfileImageFile()->getBestURI();
  }

  public function attachProfileImageFile(PhorgeFile $file) {
    $this->profileImageFile = $file;
    return $this;
  }

  public function getProfileImageFile() {
    return $this->assertAttached($this->profileImageFile);
  }

  public function getObjectName() {
    return pht('Merchant %d', $this->getID());
  }

  public function getDetailsURI() {
    return urisprintf(
      '/phortune/merchant/%d/details/',
      $this->getID());
  }

  public function getOrdersURI() {
    return urisprintf(
      '/phortune/merchant/%d/orders/',
      $this->getID());
  }

  public function getOrderListURI($path = '') {
    return urisprintf(
      '/phortune/merchant/%d/orders/list/%s',
      $this->getID(),
      $path);
  }

  public function getSubscriptionsURI() {
    return urisprintf(
      '/phortune/merchant/%d/subscriptions/',
      $this->getID());
  }

  public function getSubscriptionListURI($path = '') {
    return urisprintf(
      '/phortune/merchant/%d/subscriptions/list/%s',
      $this->getID(),
      $path);
  }

  public function getManagersURI() {
    return urisprintf(
      '/phortune/merchant/%d/managers/',
      $this->getID());
  }

  public function getPaymentProvidersURI() {
    return urisprintf(
      '/phortune/merchant/%d/providers/',
      $this->getID());
  }

/* -(  PhorgeApplicationTransactionInterface  )------------------------- */


  public function getApplicationTransactionEditor() {
    return new PhortuneMerchantEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new PhortuneMerchantTransaction();
  }


/* -(  PhorgePolicyInterface  )----------------------------------------- */


  public function getCapabilities() {
    return array(
      PhorgePolicyCapability::CAN_VIEW,
      PhorgePolicyCapability::CAN_EDIT,
    );
  }

  public function getPolicy($capability) {
    switch ($capability) {
      case PhorgePolicyCapability::CAN_VIEW:
        return PhorgePolicies::getMostOpenPolicy();
      case PhorgePolicyCapability::CAN_EDIT:
        return PhorgePolicies::POLICY_NOONE;
    }
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    $members = array_fuse($this->getMemberPHIDs());
    if (isset($members[$viewer->getPHID()])) {
      return true;
    }

    return false;
  }

  public function describeAutomaticCapability($capability) {
    return pht("A merchant's members an always view and edit it.");
  }

}
