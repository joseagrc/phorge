<?php

final class PhorgeXHProfSample
  extends PhorgeXHProfDAO
  implements PhorgePolicyInterface {

  protected $filePHID;
  protected $usTotal;
  protected $sampleRate;
  protected $hostname;
  protected $requestPath;
  protected $controller;
  protected $userPHID;

  public static function initializeNewSample() {
    return id(new self())
      ->setUsTotal(0)
      ->setSampleRate(0);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_COLUMN_SCHEMA => array(
        'sampleRate' => 'uint32',
        'usTotal' => 'uint64',
        'hostname' => 'text255?',
        'requestPath' => 'text255?',
        'controller' => 'text255?',
        'userPHID' => 'phid?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'filePHID' => array(
          'columns' => array('filePHID'),
          'unique' => true,
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function getURI() {
    return '/xhprof/profile/'.$this->getFilePHID().'/';
  }

  public function getDisplayName() {
    $request_path = $this->getRequestPath();
    if (strlen($request_path)) {
      return $request_path;
    }

    return pht('Unnamed Sample');
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
        return PhorgePolicies::getMostOpenPolicy();
    }
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    return false;
  }

}
