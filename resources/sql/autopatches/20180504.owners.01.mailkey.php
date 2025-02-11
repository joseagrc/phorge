<?php

$packages_table = new PhorgeOwnersPackage();
$packages_conn = $packages_table->establishConnection('w');
$packages_name = $packages_table->getTableName();

$properties_table = new PhorgeMetaMTAMailProperties();
$conn = $properties_table->establishConnection('w');

$iterator = new LiskRawMigrationIterator($packages_conn, $packages_name);
foreach ($iterator as $package) {
  queryfx(
    $conn,
    'INSERT IGNORE INTO %T
        (objectPHID, mailProperties, dateCreated, dateModified)
      VALUES
        (%s, %s, %d, %d)',
    $properties_table->getTableName(),
    $package['phid'],
    phutil_json_encode(
      array(
        'mailKey' => $package['mailKey'],
      )),
    PhorgeTime::getNow(),
    PhorgeTime::getNow());
}
