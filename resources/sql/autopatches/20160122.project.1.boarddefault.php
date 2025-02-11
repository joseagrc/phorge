<?php

// Populate the newish `hasWorkboard` column for projects with workboard.
// Set the default menu item to "Workboard" for projects which used to have
// that default.

$project_table = new PhorgeProject();
$conn_w = $project_table->establishConnection('w');

$panel_table = id(new PhorgeProfileMenuItemConfiguration());
$panel_conn = $panel_table->establishConnection('w');

foreach (new LiskMigrationIterator($project_table) as $project) {
  $columns = queryfx_all(
    $conn_w,
    'SELECT * FROM %T WHERE projectPHID = %s',
    id(new PhorgeProjectColumn())->getTableName(),
    $project->getPHID());

  // This project has no columns, so we don't need to change anything.
  if (!$columns) {
    continue;
  }

  // This project has columns, so set its workboard flag.
  queryfx(
    $conn_w,
    'UPDATE %T SET hasWorkboard = 1 WHERE id = %d',
    $project->getTableName(),
    $project->getID());

  // Try to set the default menu item to "Workboard".
  $config = queryfx_all(
    $panel_conn,
    'SELECT * FROM %T WHERE profilePHID = %s',
    $panel_table->getTableName(),
    $project->getPHID());

  // There are already some settings, so don't touch them.
  if ($config) {
    continue;
  }

  queryfx(
    $panel_conn,
    'INSERT INTO %T
      (phid, profilePHID, panelKey, builtinKey, visibility, panelProperties,
        panelOrder, dateCreated, dateModified)
      VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)',
    $panel_table->getTableName(),
    $panel_table->generatePHID(),
    $project->getPHID(),
    PhorgeProjectWorkboardProfileMenuItem::MENUITEMKEY,
    PhorgeProject::ITEM_WORKBOARD,
    PhorgeProfileMenuItemConfiguration::VISIBILITY_DEFAULT,
    '{}',
    2,
    PhorgeTime::getNow(),
    PhorgeTime::getNow());
}
