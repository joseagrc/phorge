/* These are here so `grep` will find them if we ever change things: */

/* PhorgeProjectProjectHasMemberEdgeType::EDGECONST = 13 */
/* PhorgeObjectHasSubscriberEdgeType::EDGECONST = 21 */

INSERT IGNORE INTO {$NAMESPACE}_project.edge (src, type, dst, dateCreated)
  SELECT src, 21, dst, dateCreated FROM {$NAMESPACE}_project.edge
    WHERE type = 13;
