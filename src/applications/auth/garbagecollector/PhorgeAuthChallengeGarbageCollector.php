<?php

final class PhorgeAuthChallengeGarbageCollector
  extends PhorgeGarbageCollector {

  const COLLECTORCONST = 'auth.challenges';

  public function getCollectorName() {
    return pht('Authentication Challenges');
  }

  public function hasAutomaticPolicy() {
    return true;
  }

  protected function collectGarbage() {
    $challenge_table = new PhorgeAuthChallenge();
    $conn = $challenge_table->establishConnection('w');

    queryfx(
      $conn,
      'DELETE FROM %R WHERE challengeTTL < UNIX_TIMESTAMP() LIMIT 100',
      $challenge_table);

    return ($conn->getAffectedRows() == 100);
  }

}
