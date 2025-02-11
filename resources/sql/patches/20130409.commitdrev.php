<?php

echo pht('Migrating %s to edges...', 'differential.revisionPHID')."\n";
$commit_table = new PhorgeRepositoryCommit();
$data_table = new PhorgeRepositoryCommitData();
$editor = new PhorgeEdgeEditor();
$commit_table->establishConnection('w');
$edges = 0;

foreach (new LiskMigrationIterator($commit_table) as $commit) {
  $data = $data_table->loadOneWhere(
    'commitID = %d',
    $commit->getID());
  if (!$data) {
    continue;
  }

  $revision_phid = $data->getCommitDetail('differential.revisionPHID');
  if (!$revision_phid) {
    continue;
  }

  $commit_drev = DiffusionCommitHasRevisionEdgeType::EDGECONST;
  $editor->addEdge($commit->getPHID(), $commit_drev, $revision_phid);
  $edges++;
  if ($edges % 256 == 0) {
    echo '.';
    $editor->save();
    $editor = new PhorgeEdgeEditor();
  }
}

echo '.';
$editor->save();
echo "\n".pht('Done.')."\n";
