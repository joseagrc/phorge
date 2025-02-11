<?php

final class PhorgeAuthSessionRevoker
  extends PhorgeAuthRevoker {

  const REVOKERKEY = 'session';

  public function getRevokerName() {
    return pht('Sessions');
  }

  public function getRevokerDescription() {
    return pht(
      "Revokes all active login sessions.\n\n".
      "Affected users will be logged out and need to log in again.");
  }

  public function revokeAllCredentials() {
    $table = new PhorgeAuthSession();
    $conn = $table->establishConnection('w');

    queryfx(
      $conn,
      'DELETE FROM %T',
      $table->getTableName());

    return $conn->getAffectedRows();
  }

  public function revokeCredentialsFrom($object) {
    $table = new PhorgeAuthSession();
    $conn = $table->establishConnection('w');

    queryfx(
      $conn,
      'DELETE FROM %T WHERE userPHID = %s',
      $table->getTableName(),
      $object->getPHID());

    return $conn->getAffectedRows();
  }

}
