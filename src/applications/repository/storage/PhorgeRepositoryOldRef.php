<?php

/**
 * Stores outdated refs which need to be checked for reachability.
 *
 * When a branch is deleted, the old HEAD ends up here and the discovery
 * engine marks all the commits that previously appeared on it as unreachable.
 */
final class PhorgeRepositoryOldRef
  extends PhorgeRepositoryDAO
  implements PhorgePolicyInterface {

  protected $repositoryPHID;
  protected $commitIdentifier;

  protected function getConfiguration() {
    return array(
      self::CONFIG_TIMESTAMPS => false,
      self::CONFIG_COLUMN_SCHEMA => array(
        'commitIdentifier' => 'text40',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_repository' => array(
          'columns' => array('repositoryPHID'),
        ),
      ),
    ) + parent::getConfiguration();
  }


/* -(  PhorgePolicyInterface  )----------------------------------------- */


  public function getCapabilities() {
    return array(
      PhorgePolicyCapability::CAN_VIEW,
    );
  }

  public function getPolicy($capability) {
    return PhorgePolicies::getMostOpenPolicy();
  }

  public function hasAutomaticCapability($capability, PhorgeUser $viewer) {
    return false;
  }

}
