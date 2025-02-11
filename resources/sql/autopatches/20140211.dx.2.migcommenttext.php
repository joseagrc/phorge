<?php

$conn_w = id(new DifferentialRevision())->establishConnection('w');
$rows = new LiskRawMigrationIterator($conn_w, 'differential_comment');

$content_source = PhorgeContentSource::newForSource(
  PhorgeOldWorldContentSource::SOURCECONST)->serialize();

echo pht('Migrating Differential comment text to modern storage...')."\n";
foreach ($rows as $row) {
  $id = $row['id'];
  echo pht('Migrating Differential comment %d...', $id)."\n";
  if (!strlen($row['content'])) {
    echo pht('Comment has no text, continuing.')."\n";
    continue;
  }

  $revision = id(new DifferentialRevision())->load($row['revisionID']);
  if (!$revision) {
    echo pht('Comment has no valid revision, continuing.')."\n";
    continue;
  }

  $revision_phid = $revision->getPHID();

  $dst_table = 'differential_inline_comment';

  $xaction_phid = PhorgePHID::generateNewPHID(
    PhorgeApplicationTransactionTransactionPHIDType::TYPECONST,
    DifferentialRevisionPHIDType::TYPECONST);

  $comment_phid = PhorgePHID::generateNewPHID(
    PhorgePHIDConstants::PHID_TYPE_XCMT,
    DifferentialRevisionPHIDType::TYPECONST);

  queryfx(
    $conn_w,
    'INSERT IGNORE INTO %T
      (phid, transactionPHID, authorPHID, viewPolicy, editPolicy,
        commentVersion, content, contentSource, isDeleted,
        dateCreated, dateModified, revisionPHID, changesetID,
        legacyCommentID)
      VALUES (%s, %s, %s, %s, %s,
        %d, %s, %s, %d,
        %d, %d, %s, %nd,
        %d)',
    'differential_transaction_comment',

    // phid, transactionPHID, authorPHID, viewPolicy, editPolicy
    $comment_phid,
    $xaction_phid,
    $row['authorPHID'],
    'public',
    $row['authorPHID'],

    // commentVersion, content, contentSource, isDeleted
    1,
    $row['content'],
    $content_source,
    0,

    // dateCreated, dateModified, revisionPHID, changesetID, legacyCommentID
    $row['dateCreated'],
    $row['dateModified'],
    $revision_phid,
    null,
    $row['id']);
}

echo pht('Done.')."\n";
