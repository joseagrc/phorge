<?php

final class PhorgeInfrastructureTestCase extends PhorgeTestCase {

  protected function getPhorgeTestCaseConfiguration() {
    return array(
      self::PHORGE_TESTCONFIG_BUILD_STORAGE_FIXTURES => true,
    );
  }

  public function testApplicationsInstalled() {
    $all = PhorgeApplication::getAllApplications();
    $installed = PhorgeApplication::getAllInstalledApplications();

    $this->assertEqual(
      count($all),
      count($installed),
      pht('In test cases, all applications should default to installed.'));
  }

  public function testRejectMySQLNonUTF8Queries() {
    $table = new HarbormasterScratchTable();
    $conn_r = $table->establishConnection('w');

    $snowman = "\xE2\x98\x83";
    $invalid = "\xE6\x9D";

    qsprintf($conn_r, 'SELECT %B', $snowman);
    qsprintf($conn_r, 'SELECT %s', $snowman);
    qsprintf($conn_r, 'SELECT %B', $invalid);

    $caught = null;
    try {
      qsprintf($conn_r, 'SELECT %s', $invalid);
    } catch (AphrontCharacterSetQueryException $ex) {
      $caught = $ex;
    }

    $this->assertTrue($caught instanceof AphrontCharacterSetQueryException);
  }

}
