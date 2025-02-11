<?php

final class PhorgeAuthInvite
  extends PhorgeUserDAO
  implements PhorgePolicyInterface {

  protected $authorPHID;
  protected $emailAddress;
  protected $verificationHash;
  protected $acceptedByPHID;

  private $verificationCode;
  private $viewerHasVerificationCode;

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'emailAddress' => 'sort128',
        'verificationHash' => 'bytes12',
        'acceptedByPHID' => 'phid?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_address' => array(
          'columns' => array('emailAddress'),
          'unique' => true,
        ),
        'key_code' => array(
          'columns' => array('verificationHash'),
          'unique' => true,
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhorgePHID::generateNewPHID(
      PhorgeAuthInvitePHIDType::TYPECONST);
  }

  public function regenerateVerificationCode() {
    $this->verificationCode = Filesystem::readRandomCharacters(16);
    $this->verificationHash = null;
    return $this;
  }

  public function getVerificationCode() {
    if (!$this->verificationCode) {
      if ($this->verificationHash) {
        throw new Exception(
          pht(
            'Verification code can not be regenerated after an invite is '.
            'created.'));
      }
      $this->regenerateVerificationCode();
    }
    return $this->verificationCode;
  }

  public function save() {
    if (!$this->getVerificationHash()) {
      $hash = PhorgeHash::digestForIndex($this->getVerificationCode());
      $this->setVerificationHash($hash);
    }

    return parent::save();
  }

  public function setViewerHasVerificationCode($loaded) {
    $this->viewerHasVerificationCode = $loaded;
    return $this;
  }


/* -(  PhorgePolicyInterface  )----------------------------------------- */


  public function getCapabilities() {
    return array(
      PhorgePolicyCapability::CAN_VIEW,
    );
  }

  public function getPolicy($capability) {
    switch ($capability) {
      case PhorgePolicyCapability::CAN_VIEW:
        return PhorgePolicies::POLICY_ADMIN;
    }
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    if ($this->viewerHasVerificationCode) {
      return true;
    }

    if ($viewer->getPHID()) {
      if ($viewer->getPHID() == $this->getAuthorPHID()) {
        // You can see invites you sent.
        return true;
      }

      if ($viewer->getPHID() == $this->getAcceptedByPHID()) {
        // You can see invites you have accepted.
        return true;
      }
    }

    return false;
  }

  public function describeAutomaticCapability($capability) {
    return pht(
      'Invites are visible to administrators, the inviting user, users with '.
      'an invite code, and the user who accepts the invite.');
  }

}
