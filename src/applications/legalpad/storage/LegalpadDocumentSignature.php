<?php

final class LegalpadDocumentSignature
  extends LegalpadDAO
  implements
    PhorgePolicyInterface,
    PhorgeConduitResultInterface {

  const VERIFIED = 0;
  const UNVERIFIED = 1;

  protected $documentPHID;
  protected $documentVersion;
  protected $signatureType;
  protected $signerPHID;
  protected $signerName;
  protected $signerEmail;
  protected $signatureData = array();
  protected $verified;
  protected $isExemption = 0;
  protected $exemptionPHID;
  protected $secretKey;

  private $document = self::ATTACHABLE;

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_SERIALIZATION => array(
        'signatureData' => self::SERIALIZATION_JSON,
      ),
      self::CONFIG_COLUMN_SCHEMA => array(
        'documentVersion' => 'uint32',
        'signatureType' => 'text4',
        'signerPHID' => 'phid?',
        'signerName' => 'text255',
        'signerEmail' => 'text255',
        'secretKey' => 'bytes20',
        'verified' => 'bool?',
        'isExemption' => 'bool',
        'exemptionPHID' => 'phid?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_signer' => array(
          'columns' => array('signerPHID', 'dateModified'),
        ),
        'secretKey' => array(
          'columns' => array('secretKey'),
        ),
        'key_document' => array(
          'columns' => array('documentPHID', 'signerPHID', 'documentVersion'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function getPHIDType() {
    return PhorgeLegalpadDocumentSignaturePHIDType::TYPECONST;
  }

  public function generatePHID() {
    return PhorgePHID::generateNewPHID($this->getPHIDType());
  }

  public function save() {
    if (!$this->getSecretKey()) {
      $this->setSecretKey(Filesystem::readRandomCharacters(20));
    }
    return parent::save();
  }

  public function isVerified() {
    return ($this->getVerified() != self::UNVERIFIED);
  }

  public function getDocument() {
    return $this->assertAttached($this->document);
  }

  public function attachDocument(LegalpadDocument $document) {
    $this->document = $document;
    return $this;
  }

  public function getSignerPHID() {
    return $this->signerPHID;
  }

  public function getIsExemption() {
    return (bool)$this->isExemption;
  }

  public function getExemptionPHID() {
    return $this->exemptionPHID;
  }

  public function getSignerName() {
    return $this->signerName;
  }

  public function getSignerEmail() {
    return $this->signerEmail;
  }

  public function getDocumentVersion() {
    return (int)$this->documentVersion;
  }

/* -(  PhorgeConduitResultInterface  )---------------------------------- */

  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('documentPHID')
        ->setType('phid')
        ->setDescription(pht('The PHID of the document')),
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('signerPHID')
        ->setType('phid?')
        ->setDescription(pht('The PHID of the signer')),
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('exemptionPHID')
        ->setType('phid?')
        ->setDescription(pht('The PHID of the user who granted the exemption')),
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('signerName')
        ->setType('string')
        ->setDescription(pht('The name used by the signer.')),
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('signerEmail')
        ->setType('string')
        ->setDescription(pht('The email used by the signer.')),
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('isExemption')
        ->setType('bool')
        ->setDescription(pht('Whether or not this signature is an exemption')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'documentPHID' => $this->getDocumentPHID(),
      'signerPHID' => $this->getSignerPHID(),
      'exemptionPHID' => $this->getExemptionPHID(),
      'signerName' => $this->getSignerName(),
      'signerEmail' => $this->getSignerEmail(),
      'isExemption' => $this->getIsExemption(),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
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
        return $this->getDocument()->getPolicy(
          PhorgePolicyCapability::CAN_EDIT);
    }
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    return ($viewer->getPHID() == $this->getSignerPHID());
  }

}
