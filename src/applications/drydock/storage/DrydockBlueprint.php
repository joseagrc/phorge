<?php

/**
 * @task resource Allocating Resources
 * @task lease Acquiring Leases
 */
final class DrydockBlueprint extends DrydockDAO
  implements
    PhorgeApplicationTransactionInterface,
    PhorgePolicyInterface,
    PhorgeCustomFieldInterface,
    PhorgeNgramsInterface,
    PhorgeProjectInterface,
    PhorgeConduitResultInterface {

  protected $className;
  protected $blueprintName;
  protected $viewPolicy;
  protected $editPolicy;
  protected $details = array();
  protected $isDisabled;

  private $implementation = self::ATTACHABLE;
  private $customFields = self::ATTACHABLE;
  private $fields = null;

  public static function initializeNewBlueprint(PhorgeUser $actor) {
    $app = id(new PhorgeApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhorgeDrydockApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      DrydockDefaultViewCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(
      DrydockDefaultEditCapability::CAPABILITY);

    return id(new DrydockBlueprint())
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy)
      ->setBlueprintName('')
      ->setIsDisabled(0);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_SERIALIZATION => array(
        'details' => self::SERIALIZATION_JSON,
      ),
      self::CONFIG_COLUMN_SCHEMA => array(
        'className' => 'text255',
        'blueprintName' => 'sort255',
        'isDisabled' => 'bool',
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhorgePHID::generateNewPHID(
      DrydockBlueprintPHIDType::TYPECONST);
  }

  public function getImplementation() {
    return $this->assertAttached($this->implementation);
  }

  public function attachImplementation(DrydockBlueprintImplementation $impl) {
    $this->implementation = $impl;
    return $this;
  }

  public function hasImplementation() {
    return ($this->implementation !== self::ATTACHABLE);
  }

  public function getDetail($key, $default = null) {
    return idx($this->details, $key, $default);
  }

  public function setDetail($key, $value) {
    $this->details[$key] = $value;
    return $this;
  }

  public function getFieldValue($key) {
    $key = "std:drydock:core:{$key}";
    $fields = $this->loadCustomFields();

    $field = idx($fields, $key);
    if (!$field) {
      throw new Exception(
        pht(
          'Unknown blueprint field "%s"!',
          $key));
    }

    return $field->getBlueprintFieldValue();
  }

  private function loadCustomFields() {
    if ($this->fields === null) {
      $field_list = PhorgeCustomField::getObjectFields(
        $this,
        PhorgeCustomField::ROLE_VIEW);
      $field_list->readFieldsFromStorage($this);

      $this->fields = $field_list->getFields();
    }
    return $this->fields;
  }

  public function logEvent($type, array $data = array()) {
    $log = id(new DrydockLog())
      ->setEpoch(PhorgeTime::getNow())
      ->setType($type)
      ->setData($data);

    $log->setBlueprintPHID($this->getPHID());

    return $log->save();
  }

  public function getURI() {
    $id = $this->getID();
    return "/drydock/blueprint/{$id}/";
  }


/* -(  Allocating Resources  )----------------------------------------------- */


  /**
   * @task resource
   */
  public function canEverAllocateResourceForLease(DrydockLease $lease) {
    return $this->getImplementation()->canEverAllocateResourceForLease(
      $this,
      $lease);
  }


  /**
   * @task resource
   */
  public function canAllocateResourceForLease(DrydockLease $lease) {
    return $this->getImplementation()->canAllocateResourceForLease(
      $this,
      $lease);
  }


  /**
   * @task resource
   */
  public function allocateResource(DrydockLease $lease) {
    return $this->getImplementation()->allocateResource(
      $this,
      $lease);
  }


  /**
   * @task resource
   */
  public function activateResource(DrydockResource $resource) {
    return $this->getImplementation()->activateResource(
      $this,
      $resource);
  }


  /**
   * @task resource
   */
  public function destroyResource(DrydockResource $resource) {
    $this->getImplementation()->destroyResource(
      $this,
      $resource);
    return $this;
  }


  /**
   * @task resource
   */
  public function getResourceName(DrydockResource $resource) {
    return $this->getImplementation()->getResourceName(
      $this,
      $resource);
  }


/* -(  Acquiring Leases  )--------------------------------------------------- */


  /**
   * @task lease
   */
  public function canAcquireLeaseOnResource(
    DrydockResource $resource,
    DrydockLease $lease) {
    return $this->getImplementation()->canAcquireLeaseOnResource(
      $this,
      $resource,
      $lease);
  }


  /**
   * @task lease
   */
  public function acquireLease(
    DrydockResource $resource,
    DrydockLease $lease) {
    return $this->getImplementation()->acquireLease(
      $this,
      $resource,
      $lease);
  }


  /**
   * @task lease
   */
  public function activateLease(
    DrydockResource $resource,
    DrydockLease $lease) {
    return $this->getImplementation()->activateLease(
      $this,
      $resource,
      $lease);
  }


  /**
   * @task lease
   */
  public function didReleaseLease(
    DrydockResource $resource,
    DrydockLease $lease) {
    $this->getImplementation()->didReleaseLease(
      $this,
      $resource,
      $lease);
    return $this;
  }


  /**
   * @task lease
   */
  public function destroyLease(
    DrydockResource $resource,
    DrydockLease $lease) {
    $this->getImplementation()->destroyLease(
      $this,
      $resource,
      $lease);
    return $this;
  }

  public function getInterface(
    DrydockResource $resource,
    DrydockLease $lease,
    $type) {

    $interface = $this->getImplementation()
      ->getInterface($this, $resource, $lease, $type);

    if (!$interface) {
      throw new Exception(
        pht(
          'Unable to build resource interface of type "%s".',
          $type));
    }

    return $interface;
  }

  public function shouldAllocateSupplementalResource(
    DrydockResource $resource,
    DrydockLease $lease) {
    return $this->getImplementation()->shouldAllocateSupplementalResource(
      $this,
      $resource,
      $lease);
  }


/* -(  PhorgeApplicationTransactionInterface  )------------------------- */


  public function getApplicationTransactionEditor() {
    return new DrydockBlueprintEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new DrydockBlueprintTransaction();
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


/* -(  PhorgeCustomFieldInterface  )------------------------------------ */


  public function getCustomFieldSpecificationForRole($role) {
    return array();
  }

  public function getCustomFieldBaseClass() {
    return 'DrydockBlueprintCustomField';
  }

  public function getCustomFields() {
    return $this->assertAttached($this->customFields);
  }

  public function attachCustomFields(PhorgeCustomFieldAttachment $fields) {
    $this->customFields = $fields;
    return $this;
  }


/* -(  PhorgeNgramsInterface  )----------------------------------------- */


  public function newNgrams() {
    return array(
      id(new DrydockBlueprintNameNgrams())
        ->setValue($this->getBlueprintName()),
    );
  }


/* -(  PhorgeConduitResultInterface  )---------------------------------- */


  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('name')
        ->setType('string')
        ->setDescription(pht('The name of this blueprint.')),
      id(new PhorgeConduitSearchFieldSpecification())
        ->setKey('type')
        ->setType('string')
        ->setDescription(pht('The type of resource this blueprint provides.')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'name' => $this->getBlueprintName(),
      'type' => $this->getImplementation()->getType(),
    );
  }

  public function getConduitSearchAttachments() {
    return array(
    );
  }

}
