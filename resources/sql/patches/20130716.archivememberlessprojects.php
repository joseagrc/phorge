<?php

echo pht('Archiving projects with no members...')."\n";

$table = new PhorgeProject();
$table->openTransaction();

foreach (new LiskMigrationIterator($table) as $project) {
  $members = PhorgeEdgeQuery::loadDestinationPHIDs(
    $project->getPHID(),
    PhorgeProjectProjectHasMemberEdgeType::EDGECONST);

  if (count($members)) {
    echo pht(
      'Project "%s" has %d members; skipping.',
      $project->getName(),
      count($members)), "\n";
    continue;
  }

  if ($project->getStatus() == PhorgeProjectStatus::STATUS_ARCHIVED) {
    echo pht(
      'Project "%s" already archived; skipping.',
      $project->getName()), "\n";
    continue;
  }

  echo pht('Archiving project "%s"...', $project->getName())."\n";
  queryfx(
    $table->establishConnection('w'),
    'UPDATE %T SET status = %s WHERE id = %d',
    $table->getTableName(),
    PhorgeProjectStatus::STATUS_ARCHIVED,
    $project->getID());
}

$table->saveTransaction();
echo "\n".pht('Done.')."\n";
