<?php

$table = new PhorgeRepositoryAuditRequest();
$conn_w = $table->establishConnection('w');

echo pht('Migrating Audit subscribers to subscriptions...')."\n";
foreach (new LiskMigrationIterator($table) as $request) {
  $id = $request->getID();

  echo pht("Migrating audit %d...\n", $id);

  if ($request->getAuditStatus() != 'cc') {
    // This isn't a "subscriber", so skip it.
    continue;
  }

  queryfx(
    $conn_w,
    'INSERT IGNORE INTO %T (src, type, dst) VALUES (%s, %d, %s)',
    PhorgeEdgeConfig::TABLE_NAME_EDGE,
    $request->getCommitPHID(),
    PhorgeObjectHasSubscriberEdgeType::EDGECONST,
    $request->getAuditorPHID());


  // Wipe the row.
  $request->delete();
}

echo pht('Done.')."\n";
