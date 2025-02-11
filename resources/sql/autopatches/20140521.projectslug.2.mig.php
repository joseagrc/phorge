<?php

$project_table = new PhorgeProject();
$table_name = $project_table->getTableName();
$conn_w = $project_table->establishConnection('w');
$slug_table_name = id(new PhorgeProjectSlug())->getTableName();
$time = PhorgeTime::getNow();

echo pht('Migrating projects to slugs...')."\n";
foreach (new LiskMigrationIterator($project_table) as $project) {
  $id = $project->getID();

  echo pht('Migrating project %d...', $id)."\n";

  $slug_text = PhorgeSlug::normalizeProjectSlug($project->getName());
  $slug = id(new PhorgeProjectSlug())
    ->loadOneWhere('slug = %s', $slug_text);

  if ($slug) {
    echo pht('Already migrated %d... Continuing.', $id)."\n";
    continue;
  }

  queryfx(
    $conn_w,
    'INSERT INTO %T (projectPHID, slug, dateCreated, dateModified) '.
    'VALUES (%s, %s, %d, %d)',
    $slug_table_name,
    $project->getPHID(),
    $slug_text,
    $time,
    $time);
  echo pht('Migrated %d.', $id)."\n";
}

echo pht('Done.')."\n";
