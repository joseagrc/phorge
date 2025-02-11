<?php

final class PhorgeDaemonLockLogGarbageCollector
  extends PhorgeGarbageCollector {

  const COLLECTORCONST = 'daemon.lock-log';

  public function getCollectorName() {
    return pht('Lock Logs');
  }

  public function getDefaultRetentionPolicy() {
    return 0;
  }

  protected function collectGarbage() {
    $table = new PhorgeDaemonLockLog();
    $conn = $table->establishConnection('w');

    queryfx(
      $conn,
      'DELETE FROM %T WHERE dateCreated < %d LIMIT 100',
      $table->getTableName(),
      $this->getGarbageEpoch());

    return ($conn->getAffectedRows() == 100);
  }

}
