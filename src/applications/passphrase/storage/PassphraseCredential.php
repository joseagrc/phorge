<?php

final class PassphraseCredential extends PassphraseDAO
  implements
    PhorgeApplicationTransactionInterface,
    PhorgePolicyInterface,
    PhorgeFlaggableInterface,
    PhorgeMentionableInterface,
    PhorgeSubscribableInterface,
    PhorgeDestructibleInterface,
    PhorgeSpacesInterface,
    PhorgeFulltextInterface,
    PhorgeFerretInterface {

  protected $name;
  protected $credentialType;
  protected $providesType;
  protected $viewPolicy;
  protected $editPolicy;
  protected $description;
  protected $username;
  protected $secretID;
  protected $isDestroyed;
  protected $isLocked = 0;
  protected $allowConduit = 0;
  protected $authorPHID;
  protected $spacePHID;

  private $secret = self::ATTACHABLE;
  private $implementation = self::ATTACHABLE;

  public static function initializeNewCredential(PhorgeUser $actor) {
    $app = id(new PhorgeApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhorgePassphraseApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(PassphraseDefaultViewCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(PassphraseDefaultEditCapability::CAPABILITY);

    return id(new PassphraseCredential())
      ->setName('')
      ->setUsername('')
      ->setDescription('')
      ->setIsDestroyed(0)
      ->setAuthorPHID($actor->getPHID())
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy)
      ->setSpacePHID($actor->getDefaultSpacePHID());
  }

  public function getMonogram() {
    return 'K'.$this->getID();
  }

  public function getURI() {
    return '/'.$this->getMonogram();
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'name' => 'text255',
        'credentialType' => 'text64',
        'providesType' => 'text64',
        'description' => 'text',
        'username' => 'text255',
        'secretID' => 'id?',
        'isDestroyed' => 'bool',
        'isLocked' => 'bool',
        'allowConduit' => 'bool',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_secret' => array(
          'columns' => array('secretID'),
          'unique' => true,
        ),
        'key_type' => array(
          'columns' => array('credentialType'),
        ),
        'key_provides' => array(
          'columns' => array('providesType'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhorgePHID::generateNewPHID(
      PassphraseCredentialPHIDType::TYPECONST);
  }

  public function attachSecret(PhutilOpaqueEnvelope $secret = null) {
    $this->secret = $secret;
    return $this;
  }

  public function getSecret() {
    return $this->assertAttached($this->secret);
  }

  public function getCredentialTypeImplementation() {
    $type = $this->getCredentialType();
    return PassphraseCredentialType::getTypeByConstant($type);
  }

  public function attachImplementation(PassphraseCredentialType $impl) {
    $this->implementation = $impl;
    return $this;
  }

  public function getImplementation() {
    return $this->assertAttached($this->implementation);
  }


/* -(  PhorgeApplicationTransactionInterface  )------------------------- */


  public function getApplicationTransactionEditor() {
    return new PassphraseCredentialTransactionEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new PassphraseCredentialTransaction();
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
        return $this->getViewPolicy();
      case PhorgePolicyCapability::CAN_EDIT:
        return $this->getEditPolicy();
    }
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    return false;
  }


/* -(  PhorgeSubscribableInterface  )----------------------------------- */


  public function isAutomaticallySubscribed($phid) {
    return false;
  }


/* -(  PhorgeDestructibleInterface  )----------------------------------- */

  public function destroyObjectPermanently(
    PhorgeDestructionEngine $engine) {

    $this->openTransaction();
      $secrets = id(new PassphraseSecret())->loadAllWhere(
        'id = %d',
        $this->getSecretID());
      foreach ($secrets as $secret) {
        $secret->delete();
      }
      $this->delete();
    $this->saveTransaction();
  }


/* -(  PhorgeSpacesInterface  )----------------------------------------- */


  public function getSpacePHID() {
    return $this->spacePHID;
  }


/* -(  PhorgeFulltextInterface  )--------------------------------------- */


  public function newFulltextEngine() {
    return new PassphraseCredentialFulltextEngine();
  }


/* -(  PhorgeFerretInterface  )----------------------------------------- */


  public function newFerretEngine() {
    return new PassphraseCredentialFerretEngine();
  }


}
