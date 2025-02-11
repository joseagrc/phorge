<?php

final class AlmanacDevice
  extends AlmanacDAO
  implements
    PhorgePolicyInterface,
    PhorgeApplicationTransactionInterface,
    PhorgeProjectInterface,
    PhorgeSSHPublicKeyInterface,
    AlmanacPropertyInterface,
    PhorgeDestructibleInterface,
    PhorgeNgramsInterface,
    PhorgeConduitResultInterface,
    PhorgeExtendedPolicyInterface {

  protected $name;
  protected $nameIndex;
  protected $viewPolicy;
  protected $editPolicy;
  protected $status;
  protected $isBoundToClusterService;

  private $almanacProperties = self::ATTACHABLE;

  public static function initializeNewDevice() {
    return id(new AlmanacDevice())
      ->setViewPolicy(PhorgePolicies::POLICY_USER)
      ->setEditPolicy(PhorgePolicies::POLICY_ADMIN)
      ->setStatus(AlmanacDeviceStatus::ACTIVE)
      ->attachAlmanacProperties(array())
      ->setIsBoundToClusterService(0);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'name' => 'text128',
        'nameIndex' => 'bytes12',
        'status' => 'text32',
        'isBoundToClusterService' => 'bool',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_name' => array(
          'columns' => array('nameIndex'),
          'unique' => true,
        ),
        'key_nametext' => array(
          'columns' => array('name'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function getPHIDType() {
    return AlmanacDevicePHIDType::TYPECONST;
  }

  public function save() {
    AlmanacNames::validateName($this->getName());

    $this->nameIndex = PhorgeHash::digestForIndex($this->getName());

    return parent::save();
  }

  public function getURI() {
    return urisprintf(
      '/almanac/device/view/%s/',
      $this->getName());
  }

  public function rebuildClusterBindingStatus() {
    $services = id(new AlmanacServiceQuery())
      ->setViewer(PhorgeUser::getOmnipotentUser())
      ->withDevicePHIDs(array($this->getPHID()))
      ->execute();

    $is_cluster = false;
    foreach ($services as $service) {
      if ($service->isClusterService()) {
        $is_cluster = true;
        break;
      }
    }

    if ($is_cluster != $this->getIsBoundToClusterService()) {
      $this->setIsBoundToClusterService((int)$is_cluster);
      $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        queryfx(
          $this->establishConnection('w'),
          'UPDATE %R SET isBoundToClusterService = %d WHERE id = %d',
          $this,
          $this->getIsBoundToClusterService(),
          $this->getID());
      unset($unguarded);
    }

    return $this;
  }

  public function isClusterDevice() {
    return $this->getIsBoundToClusterService();
  }

  public function getStatusObject() {
    return $this->newStatusObject();
  }

  private function newStatusObject() {
    return AlmanacDeviceStatus::newStatusFromValue($this->getStatus());
  }

  public function isDisabled() {
    return $this->getStatusObject()->isDisabled();
  }


/* -(  AlmanacPropertyInterface  )------------------------------------------- */


  public function attachAlmanacProperties(array $properties) {
    assert_instances_of($properties, 'AlmanacProperty');
    $this->almanacProperties = mpull($properties, null, 'getFieldName');
    return $this;
  }

  public function getAlmanacProperties() {
    return $this->assertAttached($this->almanacProperties);
  }

  public function hasAlmanacProperty($key) {
    $this->assertAttached($this->almanacProperties);
    return isset($this->almanacProperties[$key]);
  }

  public function getAlmanacProperty($key) {
    return $this->assertAttachedKey($this->almanacProperties, $key);
  }

  public function getAlmanacPropertyValue($key, $default = null) {
    if ($this->hasAlmanacProperty($key)) {
      return $this->getAlmanacProperty($key)->getFieldValue();
    } else {
      return $default;
    }
  }

  public function getAlmanacPropertyFieldSpecifications() {
    return array();
  }

  public function newAlmanacPropertyEditEngine() {
    return new AlmanacDevicePropertyEditEngine();
  }

  public function getAlmanacPropertySetTransactionType() {
    return AlmanacDeviceSetPropertyTransaction::TRANSACTIONTYPE;
  }

  public function getAlmanacPropertyDeleteTransactionType() {
    return AlmanacDeviceDeletePropertyTransaction::TRANSACTIONTYPE;
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


/* -(  PhorgeExtendedPolicyInterface  )--------------------------------- */


  public function getExtendedPolicy($capability, PhorgeUser $viewer) {
    switch ($capability) {
      case PhorgePolicyCapability::CAN_EDIT:
        if ($this->isClusterDevice()) {
          return array(
            array(
              new PhorgeAlmanacApplication(),
              AlmanacManageClusterServicesCapability::CAPABILITY,
            ),
          );
        }
        break;
    }

    return array();
  }


/* -(  PhorgeApplicationTransactionInterface  )------------------------- */


  public function getApplicationTransactionEditor() {
    return new AlmanacDeviceEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new AlmanacDeviceTransaction();
  }


/* -(  PhorgeSSHPublicKeyInterface  )----------------------------------- */


  public function getSSHPublicKeyManagementURI(PhorgeUser $viewer) {
    return $this->getURI();
  }

  public function getSSHKeyDefaultName() {
    return $this->getName();
  }

  public function getSSHKeyNotifyPHIDs() {
    // Devices don't currently have anyone useful to notify about SSH key
    // edits, and they're usually a difficult vector to attack since you need
    // access to a cluster host. However, it would be nice to make them
    // subscribable at some point.
    return array();
  }


/* -(  PhorgeDestructibleInterface  )----------------------------------- */


  public function destroyObjectPermanently(
    PhorgeDestructionEngine $engine) {

    $interfaces = id(new AlmanacInterfaceQuery())
      ->setViewer($engine->getViewer())
      ->withDevicePHIDs(array($this->getPHID()))
      ->execute();
    foreach ($interfaces as $interface) {
      $engine->destroyObject($interface);
    }

    $this->delete();
  }


/* -(  PhorgeNgramsInterface  )----------------------------------------- */


  public function newNgrams() {
    return array(
      id(new AlmanacDeviceNameNgrams())
        ->setValue($this->getName()),
    );
  }


/* -(  PhorgeConduitResultInterface  )---------------------------------- */


  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('name')
        ->setType('string')
        ->setDescription(pht('The name of the device.')),
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('status')
        ->setType('map<string, wild>')
        ->setDescription(pht('Device status information.')),
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('disabled')
        ->setType('bool')
        ->setDescription(pht('True if device is disabled.')),
    );
  }

  public function getFieldValuesForConduit() {
    $status = $this->getStatusObject();

    return array(
      'name' => $this->getName(),
      'status' => array(
        'value' => $status->getValue(),
        'name' => $status->getName(),
      ),
      'disabled' => $this->isDisabled(),
    );
  }

  public function getConduitSearchAttachments() {
    return array(
      id(new AlmanacPropertiesSearchEngineAttachment())
        ->setAttachmentKey('properties'),
    );
  }

}
