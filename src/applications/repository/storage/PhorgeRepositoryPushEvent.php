<?php

/**
 * Groups a set of push logs corresponding to changes which were all pushed in
 * the same transaction.
 */
final class PhorgeRepositoryPushEvent
  extends PhorgeRepositoryDAO
  implements PhorgePolicyInterface {

  protected $repositoryPHID;
  protected $epoch;
  protected $pusherPHID;
  protected $requestIdentifier;
  protected $remoteAddress;
  protected $remoteProtocol;
  protected $rejectCode;
  protected $rejectDetails;
  protected $writeWait;
  protected $readWait;
  protected $hostWait;
  protected $hookWait;

  private $repository = self::ATTACHABLE;
  private $logs = self::ATTACHABLE;

  public static function initializeNewEvent(PhorgeUser $viewer) {
    return id(new PhorgeRepositoryPushEvent())
      ->setPusherPHID($viewer->getPHID());
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_TIMESTAMPS => false,
      self::CONFIG_COLUMN_SCHEMA => array(
        'requestIdentifier' => 'bytes12?',
        'remoteAddress' => 'ipaddress?',
        'remoteProtocol' => 'text32?',
        'rejectCode' => 'uint32',
        'rejectDetails' => 'text64?',
        'writeWait' => 'uint64?',
        'readWait' => 'uint64?',
        'hostWait' => 'uint64?',
        'hookWait' => 'uint64?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_repository' => array(
          'columns' => array('repositoryPHID'),
        ),
        'key_identifier' => array(
          'columns' => array('requestIdentifier'),
        ),
        'key_reject' => array(
          'columns' => array('rejectCode', 'rejectDetails'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhorgePHID::generateNewPHID(
      PhorgeRepositoryPushEventPHIDType::TYPECONST);
  }

  public function attachRepository(PhorgeRepository $repository) {
    $this->repository = $repository;
    return $this;
  }

  public function getRepository() {
    return $this->assertAttached($this->repository);
  }

  public function attachLogs(array $logs) {
    $this->logs = $logs;
    return $this;
  }

  public function getLogs() {
    return $this->assertAttached($this->logs);
  }

  public function saveWithLogs(array $logs) {
    assert_instances_of($logs, 'PhorgeRepositoryPushLog');

    $this->openTransaction();
      $this->save();
      foreach ($logs as $log) {
        $log->setPushEventPHID($this->getPHID());
        $log->save();
      }
    $this->saveTransaction();

    $this->attachLogs($logs);

    return $this;
  }

/* -(  PhorgePolicyInterface  )----------------------------------------- */


  public function getCapabilities() {
    return array(
      PhorgePolicyCapability::CAN_VIEW,
    );
  }

  public function getPolicy($capability) {
    return $this->getRepository()->getPolicy($capability);
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    return $this->getRepository()->hasAutomaticCapability($capability, $viewer);
  }

  public function describeAutomaticCapability($capability) {
    return pht(
      "A repository's push events are visible to users who can see the ".
      "repository.");
  }

}
