INSERT INTO {$NAMESPACE}_differential.differential_auxiliaryfield
  (revisionPHID, name, value, dateCreated, dateModified)
SELECT phid, 'phorge:blame-revision', blameRevision,
    dateCreated, dateModified
  FROM {$NAMESPACE}_differential.differential_revision
  WHERE blameRevision != '';

ALTER TABLE {$NAMESPACE}_differential.differential_revision
  DROP blameRevision;


INSERT INTO {$NAMESPACE}_differential.differential_auxiliaryfield
  (revisionPHID, name, value, dateCreated, dateModified)
SELECT phid, 'phorge:revert-plan', revertPlan,
    dateCreated, dateModified
  FROM {$NAMESPACE}_differential.differential_revision
  WHERE revertPlan != '';

ALTER TABLE {$NAMESPACE}_differential.differential_revision
  DROP revertPlan;
