<?php

final class NuanceImportCursorData
  extends NuanceDAO
  implements PhorgePolicyInterface {

  protected $sourcePHID;
  protected $cursorKey;
  protected $cursorType;
  protected $properties = array();

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_SERIALIZATION => array(
        'properties' => self::SERIALIZATION_JSON,
      ),
      self::CONFIG_COLUMN_SCHEMA => array(
        'cursorType' => 'text32',
        'cursorKey' => 'text32',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_source' => array(
          'columns' => array('sourcePHID', 'cursorKey'),
          'unique' => true,
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhorgePHID::generateNewPHID(
      NuanceImportCursorPHIDType::TYPECONST);
  }

  public function getCursorProperty($key, $default = null) {
    return idx($this->properties, $key, $default);
  }

  public function setCursorProperty($key, $value) {
    $this->properties[$key] = $value;
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
        return PhorgePolicies::POLICY_USER;
    }
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    return false;
  }

}
