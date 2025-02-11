<?php

/**
 * @task discover   Discovering Repositories
 * @task svn        Discovering Subversion Repositories
 * @task git        Discovering Git Repositories
 * @task hg         Discovering Mercurial Repositories
 * @task internal   Internals
 */
final class PhorgeRepositoryDiscoveryEngine
  extends PhorgeRepositoryEngine {

  private $repairMode;
  private $commitCache = array();
  private $workingSet = array();

  const MAX_COMMIT_CACHE_SIZE = 65535;


/* -(  Discovering Repositories  )------------------------------------------- */


  public function setRepairMode($repair_mode) {
    $this->repairMode = $repair_mode;
    return $this;
  }


  public function getRepairMode() {
    return $this->repairMode;
  }


  /**
   * @task discovery
   */
  public function discoverCommits() {
    $repository = $this->getRepository();

    $lock = $this->newRepositoryLock($repository, 'repo.look', false);

    try {
      $lock->lock();
    } catch (PhutilLockException $ex) {
      throw new DiffusionDaemonLockException(
        pht(
          'Another process is currently discovering repository "%s", '.
          'skipping discovery.',
          $repository->getDisplayName()));
    }

    try {
      $result = $this->discoverCommitsWithLock();
    } catch (Exception $ex) {
      $lock->unlock();
      throw $ex;
    }

    $lock->unlock();

    return $result;
  }

  private function discoverCommitsWithLock() {
    $repository = $this->getRepository();
    $viewer = $this->getViewer();

    $vcs = $repository->getVersionControlSystem();
    switch ($vcs) {
      case PhorgeRepositoryType::REPOSITORY_TYPE_SVN:
        $refs = $this->discoverSubversionCommits();
        break;
      case PhorgeRepositoryType::REPOSITORY_TYPE_MERCURIAL:
        $refs = $this->discoverMercurialCommits();
        break;
      case PhorgeRepositoryType::REPOSITORY_TYPE_GIT:
        $refs = $this->discoverGitCommits();
        break;
      default:
        throw new Exception(pht("Unknown VCS '%s'!", $vcs));
    }

    if ($this->isInitialImport($refs)) {
      $this->log(
        pht(
          'Discovered more than %s commit(s) in an empty repository, '.
          'marking repository as importing.',
          new PhutilNumber(PhorgeRepository::IMPORT_THRESHOLD)));

      $repository->markImporting();
    }

    // Clear the working set cache.
    $this->workingSet = array();

    $task_priority = $this->getImportTaskPriority($repository, $refs);

    // Record discovered commits and mark them in the cache.
    foreach ($refs as $ref) {
      $this->recordCommit(
        $repository,
        $ref->getIdentifier(),
        $ref->getEpoch(),
        $ref->getIsPermanent(),
        $ref->getParents(),
        $task_priority);

      $this->commitCache[$ref->getIdentifier()] = true;
    }

    $this->markUnreachableCommits($repository);

    $version = $this->getObservedVersion($repository);
    if ($version !== null) {
      id(new DiffusionRepositoryClusterEngine())
        ->setViewer($viewer)
        ->setRepository($repository)
        ->synchronizeWorkingCopyAfterDiscovery($version);
    }

    return $refs;
  }


/* -(  Discovering Git Repositories  )--------------------------------------- */


  /**
   * @task git
   */
  private function discoverGitCommits() {
    $repository = $this->getRepository();
    $publisher = $repository->newPublisher();

    $heads = id(new DiffusionLowLevelGitRefQuery())
      ->setRepository($repository)
      ->execute();

    if (!$heads) {
      // This repository has no heads at all, so we don't need to do
      // anything. Generally, this means the repository is empty.
      return array();
    }

    $this->log(
      pht(
        'Discovering commits in repository "%s".',
        $repository->getDisplayName()));

    $ref_lists = array();

    $head_groups = $this->getRefGroupsForDiscovery($heads);
    foreach ($head_groups as $head_group) {

      $group_identifiers = mpull($head_group, 'getCommitIdentifier');
      $group_identifiers = array_fuse($group_identifiers);
      $this->fillCommitCache($group_identifiers);

      foreach ($head_group as $ref) {
        $name = $ref->getShortName();
        $commit = $ref->getCommitIdentifier();

        $this->log(
          pht(
            'Examining "%s" (%s) at "%s".',
            $name,
            $ref->getRefType(),
            $commit));

        if (!$repository->shouldTrackRef($ref)) {
          $this->log(pht('Skipping, ref is untracked.'));
          continue;
        }

        if ($this->isKnownCommit($commit)) {
          $this->log(pht('Skipping, HEAD is known.'));
          continue;
        }

        // In Git, it's possible to tag anything. We just skip tags that don't
        // point to a commit. See T11301.
        $fields = $ref->getRawFields();
        $ref_type = idx($fields, 'objecttype');
        $tag_type = idx($fields, '*objecttype');
        if ($ref_type != 'commit' && $tag_type != 'commit') {
          $this->log(pht('Skipping, this is not a commit.'));
          continue;
        }

        $this->log(pht('Looking for new commits.'));

        $head_refs = $this->discoverStreamAncestry(
          new PhorgeGitGraphStream($repository, $commit),
          $commit,
          $publisher->isPermanentRef($ref));

        $this->didDiscoverRefs($head_refs);

        $ref_lists[] = $head_refs;
      }
    }

    $refs = array_mergev($ref_lists);

    return $refs;
  }

  /**
   * @task git
   */
  private function getRefGroupsForDiscovery(array $heads) {
    $heads = $this->sortRefs($heads);

    // See T13593. We hold a commit cache with a fixed maximum size. Split the
    // refs into chunks no larger than the cache size, so we don't overflow the
    // cache when testing them.

    $array_iterator = new ArrayIterator($heads);

    $chunk_iterator = new PhutilChunkedIterator(
      $array_iterator,
      self::MAX_COMMIT_CACHE_SIZE);

    return $chunk_iterator;
  }


/* -(  Discovering Subversion Repositories  )-------------------------------- */


  /**
   * @task svn
   */
  private function discoverSubversionCommits() {
    $repository = $this->getRepository();

    if (!$repository->isHosted()) {
      $this->verifySubversionRoot($repository);
    }

    $upper_bound = null;
    $limit = 1;
    $refs = array();
    do {
      // Find all the unknown commits on this path. Note that we permit
      // importing an SVN subdirectory rather than the entire repository, so
      // commits may be nonsequential.

      if ($upper_bound === null) {
        $at_rev = 'HEAD';
      } else {
        $at_rev = ($upper_bound - 1);
      }

      try {
        list($xml, $stderr) = $repository->execxRemoteCommand(
          'log --xml --quiet --limit %d %s',
          $limit,
          $repository->getSubversionBaseURI($at_rev));
      } catch (CommandException $ex) {
        $stderr = $ex->getStderr();
        if (preg_match('/(path|File) not found/', $stderr)) {
          // We've gone all the way back through history and this path was not
          // affected by earlier commits.
          break;
        }
        throw $ex;
      }

      $xml = phutil_utf8ize($xml);
      $log = new SimpleXMLElement($xml);
      foreach ($log->logentry as $entry) {
        $identifier = (int)$entry['revision'];
        $epoch = (int)strtotime((string)$entry->date[0]);
        $refs[$identifier] = id(new PhorgeRepositoryCommitRef())
          ->setIdentifier($identifier)
          ->setEpoch($epoch)
          ->setIsPermanent(true);

        if ($upper_bound === null) {
          $upper_bound = $identifier;
        } else {
          $upper_bound = min($upper_bound, $identifier);
        }
      }

      // Discover 2, 4, 8, ... 256 logs at a time. This allows us to initially
      // import large repositories fairly quickly, while pulling only as much
      // data as we need in the common case (when we've already imported the
      // repository and are just grabbing one commit at a time).
      $limit = min($limit * 2, 256);

    } while ($upper_bound > 1 && !$this->isKnownCommit($upper_bound));

    krsort($refs);
    while ($refs && $this->isKnownCommit(last($refs)->getIdentifier())) {
      array_pop($refs);
    }
    $refs = array_reverse($refs);

    $this->didDiscoverRefs($refs);

    return $refs;
  }


  private function verifySubversionRoot(PhorgeRepository $repository) {
    list($xml) = $repository->execxRemoteCommand(
      'info --xml %s',
      $repository->getSubversionPathURI());

    $xml = phutil_utf8ize($xml);
    $xml = new SimpleXMLElement($xml);

    $remote_root = (string)($xml->entry[0]->repository[0]->root[0]);
    $expect_root = $repository->getSubversionPathURI();

    $normal_type_svn = ArcanistRepositoryURINormalizer::TYPE_SVN;

    $remote_normal = id(new ArcanistRepositoryURINormalizer(
      $normal_type_svn,
      $remote_root))->getNormalizedPath();

    $expect_normal = id(new ArcanistRepositoryURINormalizer(
      $normal_type_svn,
      $expect_root))->getNormalizedPath();

    if ($remote_normal != $expect_normal) {
      throw new Exception(
        pht(
          'Repository "%s" does not have a correctly configured remote URI. '.
          'The remote URI for a Subversion repository MUST point at the '.
          'repository root. The root for this repository is "%s", but the '.
          'configured URI is "%s". To resolve this error, set the remote URI '.
          'to point at the repository root. If you want to import only part '.
          'of a Subversion repository, use the "Import Only" option.',
          $repository->getDisplayName(),
          $remote_root,
          $expect_root));
    }
  }


/* -(  Discovering Mercurial Repositories  )--------------------------------- */


  /**
   * @task hg
   */
  private function discoverMercurialCommits() {
    $repository = $this->getRepository();

    $branches = id(new DiffusionLowLevelMercurialBranchesQuery())
      ->setRepository($repository)
      ->execute();

    $this->fillCommitCache(mpull($branches, 'getCommitIdentifier'));

    $refs = array();
    foreach ($branches as $branch) {
      // NOTE: Mercurial branches may have multiple heads, so the names may
      // not be unique.
      $name = $branch->getShortName();
      $commit = $branch->getCommitIdentifier();

      $this->log(pht('Examining branch "%s" head "%s".', $name, $commit));
      if (!$repository->shouldTrackBranch($name)) {
        $this->log(pht('Skipping, branch is untracked.'));
        continue;
      }

      if ($this->isKnownCommit($commit)) {
        $this->log(pht('Skipping, this head is a known commit.'));
        continue;
      }

      $this->log(pht('Looking for new commits.'));

      $branch_refs = $this->discoverStreamAncestry(
        new PhorgeMercurialGraphStream($repository, $commit),
        $commit,
        $is_permanent = true);

      $this->didDiscoverRefs($branch_refs);

      $refs[] = $branch_refs;
    }

    return array_mergev($refs);
  }


/* -(  Internals  )---------------------------------------------------------- */


  private function discoverStreamAncestry(
    PhorgeRepositoryGraphStream $stream,
    $commit,
    $is_permanent) {

    $discover = array($commit);
    $graph = array();
    $seen = array();

    // Find all the reachable, undiscovered commits. Build a graph of the
    // edges.
    while ($discover) {
      $target = array_pop($discover);

      if (empty($graph[$target])) {
        $graph[$target] = array();
      }

      $parents = $stream->getParents($target);
      foreach ($parents as $parent) {
        if ($this->isKnownCommit($parent)) {
          continue;
        }

        $graph[$target][$parent] = true;

        if (empty($seen[$parent])) {
          $seen[$parent] = true;
          $discover[] = $parent;
        }
      }
    }

    // Now, sort them topologically.
    $commits = $this->reduceGraph($graph);

    $refs = array();
    foreach ($commits as $commit) {
      $epoch = $stream->getCommitDate($commit);

      // If the epoch doesn't fit into a uint32, treat it as though it stores
      // the current time. For discussion, see T11537.
      if ($epoch > 0xFFFFFFFF) {
        $epoch = PhorgeTime::getNow();
      }

      // If the epoch is not present at all, treat it as though it stores the
      // value "0". For discussion, see T12062. This behavior is consistent
      // with the behavior of "git show".
      if (!strlen($epoch)) {
        $epoch = 0;
      }

      $refs[] = id(new PhorgeRepositoryCommitRef())
        ->setIdentifier($commit)
        ->setEpoch($epoch)
        ->setIsPermanent($is_permanent)
        ->setParents($stream->getParents($commit));
    }

    return $refs;
  }


  private function reduceGraph(array $edges) {
    foreach ($edges as $commit => $parents) {
      $edges[$commit] = array_keys($parents);
    }

    $graph = new PhutilDirectedScalarGraph();
    $graph->addNodes($edges);

    $commits = $graph->getNodesInTopologicalOrder();

    // NOTE: We want the most ancestral nodes first, so we need to reverse the
    // list we get out of AbstractDirectedGraph.
    $commits = array_reverse($commits);

    return $commits;
  }


  private function isKnownCommit($identifier) {
    if (isset($this->commitCache[$identifier])) {
      return true;
    }

    if (isset($this->workingSet[$identifier])) {
      return true;
    }

    $this->fillCommitCache(array($identifier));

    return isset($this->commitCache[$identifier]);
  }

  private function fillCommitCache(array $identifiers) {
    if (!$identifiers) {
      return;
    }

    if ($this->repairMode) {
      // In repair mode, rediscover the entire repository, ignoring the
      // database state. The engine still maintains a local cache (the
      // "Working Set") but we just give up before looking in the database.
      return;
    }

    $max_size = self::MAX_COMMIT_CACHE_SIZE;

    // If we're filling more identifiers than would fit in the cache, ignore
    // the ones that don't fit. Because the cache is FIFO, overfilling it can
    // cause the entire cache to miss. See T12296.
    if (count($identifiers) > $max_size) {
      $identifiers = array_slice($identifiers, 0, $max_size);
    }

    // When filling the cache we ignore commits which have been marked as
    // unreachable, treating them as though they do not exist. When recording
    // commits later we'll revive commits that exist but are unreachable.

    $commits = id(new PhorgeRepositoryCommit())->loadAllWhere(
      'repositoryID = %d AND commitIdentifier IN (%Ls)
        AND (importStatus & %d) != %d',
      $this->getRepository()->getID(),
      $identifiers,
      PhorgeRepositoryCommit::IMPORTED_UNREACHABLE,
      PhorgeRepositoryCommit::IMPORTED_UNREACHABLE);

    foreach ($commits as $commit) {
      $this->commitCache[$commit->getCommitIdentifier()] = true;
    }

    while (count($this->commitCache) > $max_size) {
      array_shift($this->commitCache);
    }
  }

  /**
   * Sort refs so we process permanent refs first. This makes the whole import
   * process a little cheaper, since we can publish these commits the first
   * time through rather than catching them in the refs step.
   *
   * @task internal
   *
   * @param   list<DiffusionRepositoryRef> List of refs.
   * @return  list<DiffusionRepositoryRef> Sorted list of refs.
   */
  private function sortRefs(array $refs) {
    $repository = $this->getRepository();
    $publisher = $repository->newPublisher();

    $head_refs = array();
    $tail_refs = array();
    foreach ($refs as $ref) {
      if ($publisher->isPermanentRef($ref)) {
        $head_refs[] = $ref;
      } else {
        $tail_refs[] = $ref;
      }
    }

    return array_merge($head_refs, $tail_refs);
  }


  private function recordCommit(
    PhorgeRepository $repository,
    $commit_identifier,
    $epoch,
    $is_permanent,
    array $parents,
    $task_priority) {

    $commit = new PhorgeRepositoryCommit();
    $conn_w = $repository->establishConnection('w');

    // First, try to revive an existing unreachable commit (if one exists) by
    // removing the "unreachable" flag. If we succeed, we don't need to do
    // anything else: we already discovered this commit some time ago.
    queryfx(
      $conn_w,
      'UPDATE %T SET importStatus = (importStatus & ~%d)
        WHERE repositoryID = %d AND commitIdentifier = %s',
      $commit->getTableName(),
      PhorgeRepositoryCommit::IMPORTED_UNREACHABLE,
      $repository->getID(),
      $commit_identifier);
    if ($conn_w->getAffectedRows()) {
      $commit = $commit->loadOneWhere(
        'repositoryID = %d AND commitIdentifier = %s',
        $repository->getID(),
        $commit_identifier);

      // After reviving a commit, schedule new daemons for it.
      $this->didDiscoverCommit($repository, $commit, $epoch, $task_priority);
      return;
    }

    $commit->setRepositoryID($repository->getID());
    $commit->setCommitIdentifier($commit_identifier);
    $commit->setEpoch($epoch);
    if ($is_permanent) {
      $commit->setImportStatus(PhorgeRepositoryCommit::IMPORTED_PERMANENT);
    }

    $data = new PhorgeRepositoryCommitData();

    try {
      // If this commit has parents, look up their IDs. The parent commits
      // should always exist already.

      $parent_ids = array();
      if ($parents) {
        $parent_rows = queryfx_all(
          $conn_w,
          'SELECT id, commitIdentifier FROM %T
            WHERE commitIdentifier IN (%Ls) AND repositoryID = %d',
          $commit->getTableName(),
          $parents,
          $repository->getID());

        $parent_map = ipull($parent_rows, 'id', 'commitIdentifier');

        foreach ($parents as $parent) {
          if (empty($parent_map[$parent])) {
            throw new Exception(
              pht('Unable to identify parent "%s"!', $parent));
          }
          $parent_ids[] = $parent_map[$parent];
        }
      } else {
        // Write an explicit 0 so we can distinguish between "really no
        // parents" and "data not available".
        if (!$repository->isSVN()) {
          $parent_ids = array(0);
        }
      }

      $commit->openTransaction();
        $commit->save();

        $data->setCommitID($commit->getID());
        $data->save();

        foreach ($parent_ids as $parent_id) {
          queryfx(
            $conn_w,
            'INSERT IGNORE INTO %T (childCommitID, parentCommitID)
              VALUES (%d, %d)',
            PhorgeRepository::TABLE_PARENTS,
            $commit->getID(),
            $parent_id);
        }
      $commit->saveTransaction();

      $this->didDiscoverCommit($repository, $commit, $epoch, $task_priority);

      if ($this->repairMode) {
        // Normally, the query should throw a duplicate key exception. If we
        // reach this in repair mode, we've actually performed a repair.
        $this->log(pht('Repaired commit "%s".', $commit_identifier));
      }

      PhutilEventEngine::dispatchEvent(
        new PhorgeEvent(
          PhorgeEventType::TYPE_DIFFUSION_DIDDISCOVERCOMMIT,
          array(
            'repository'  => $repository,
            'commit'      => $commit,
          )));

    } catch (AphrontDuplicateKeyQueryException $ex) {
      $commit->killTransaction();
      // Ignore. This can happen because we discover the same new commit
      // more than once when looking at history, or because of races or
      // data inconsistency or cosmic radiation; in any case, we're still
      // in a good state if we ignore the failure.
    }
  }

  private function didDiscoverCommit(
    PhorgeRepository $repository,
    PhorgeRepositoryCommit $commit,
    $epoch,
    $task_priority) {

    $this->queueCommitImportTask(
      $repository,
      $commit->getPHID(),
      $task_priority,
      $via = 'discovery');

    // Update the repository summary table.
    queryfx(
      $commit->establishConnection('w'),
      'INSERT INTO %T (repositoryID, size, lastCommitID, epoch)
        VALUES (%d, 1, %d, %d)
        ON DUPLICATE KEY UPDATE
          size = size + 1,
          lastCommitID =
            IF(VALUES(epoch) > epoch, VALUES(lastCommitID), lastCommitID),
          epoch = IF(VALUES(epoch) > epoch, VALUES(epoch), epoch)',
      PhorgeRepository::TABLE_SUMMARY,
      $repository->getID(),
      $commit->getID(),
      $epoch);
  }

  private function didDiscoverRefs(array $refs) {
    foreach ($refs as $ref) {
      $this->workingSet[$ref->getIdentifier()] = true;
    }
  }

  private function isInitialImport(array $refs) {
    $commit_count = count($refs);

    if ($commit_count <= PhorgeRepository::IMPORT_THRESHOLD) {
      // If we fetched a small number of commits, assume it's an initial
      // commit or a stack of a few initial commits.
      return false;
    }

    $viewer = $this->getViewer();
    $repository = $this->getRepository();

    $any_commits = id(new DiffusionCommitQuery())
      ->setViewer($viewer)
      ->withRepository($repository)
      ->setLimit(1)
      ->execute();

    if ($any_commits) {
      // If the repository already has commits, this isn't an import.
      return false;
    }

    return true;
  }


  private function getObservedVersion(PhorgeRepository $repository) {
    if ($repository->isHosted()) {
      return null;
    }

    if ($repository->isGit()) {
      return $this->getGitObservedVersion($repository);
    }

    return null;
  }

  private function getGitObservedVersion(PhorgeRepository $repository) {
    $refs = id(new DiffusionLowLevelGitRefQuery())
     ->setRepository($repository)
     ->execute();
    if (!$refs) {
      return null;
    }

    // In Git, the observed version is the most recently discovered commit
    // at any repository HEAD. It's possible for this to regress temporarily
    // if a branch is pushed and then deleted. This is acceptable because it
    // doesn't do anything meaningfully bad and will fix itself on the next
    // push.

    $ref_identifiers = mpull($refs, 'getCommitIdentifier');
    $ref_identifiers = array_fuse($ref_identifiers);

    $version = queryfx_one(
      $repository->establishConnection('w'),
      'SELECT MAX(id) version FROM %T WHERE repositoryID = %d
        AND commitIdentifier IN (%Ls)',
      id(new PhorgeRepositoryCommit())->getTableName(),
      $repository->getID(),
      $ref_identifiers);

    if (!$version) {
      return null;
    }

    return (int)$version['version'];
  }

  private function markUnreachableCommits(PhorgeRepository $repository) {
    if (!$repository->isGit() && !$repository->isHg()) {
      return;
    }

    // Find older versions of refs which we haven't processed yet. We're going
    // to make sure their commits are still reachable.
    $old_refs = id(new PhorgeRepositoryOldRef())->loadAllWhere(
      'repositoryPHID = %s',
      $repository->getPHID());

    // If we don't have any refs to update, bail out before building a graph
    // stream. In particular, this improves behavior in empty repositories,
    // where `git log` exits with an error.
    if (!$old_refs) {
      return;
    }

    // We can share a single graph stream across all the checks we need to do.
    if ($repository->isGit()) {
      $stream = new PhorgeGitGraphStream($repository);
    } else if ($repository->isHg()) {
      $stream = new PhorgeMercurialGraphStream($repository);
    }

    foreach ($old_refs as $old_ref) {
      $identifier = $old_ref->getCommitIdentifier();
      $this->markUnreachableFrom($repository, $stream, $identifier);

      // If nothing threw an exception, we're all done with this ref.
      $old_ref->delete();
    }
  }

  private function markUnreachableFrom(
    PhorgeRepository $repository,
    PhorgeRepositoryGraphStream $stream,
    $identifier) {

    $unreachable = array();

    $commit = id(new PhorgeRepositoryCommit())->loadOneWhere(
      'repositoryID = %s AND commitIdentifier = %s',
      $repository->getID(),
      $identifier);
    if (!$commit) {
      return;
    }

    $look = array($commit);
    $seen = array();
    while ($look) {
      $target = array_pop($look);

      // If we've already checked this commit (for example, because history
      // branches and then merges) we don't need to check it again.
      $target_identifier = $target->getCommitIdentifier();
      if (isset($seen[$target_identifier])) {
        continue;
      }

      $seen[$target_identifier] = true;

      // See PHI1688. If this commit is already marked as unreachable, we don't
      // need to consider its ancestors. This may skip a lot of work if many
      // branches with a lot of shared ancestry are deleted at the same time.
      if ($target->isUnreachable()) {
        continue;
      }

      try {
        $stream->getCommitDate($target_identifier);
        $reachable = true;
      } catch (Exception $ex) {
        $reachable = false;
      }

      if ($reachable) {
        // This commit is reachable, so we don't need to go any further
        // down this road.
        continue;
      }

      $unreachable[] = $target;

      // Find the commit's parents and check them for reachability, too. We
      // have to look in the database since we no may longer have the commit
      // in the repository.
      $rows = queryfx_all(
        $commit->establishConnection('w'),
        'SELECT commit.* FROM %T commit
          JOIN %T parents ON commit.id = parents.parentCommitID
          WHERE parents.childCommitID = %d',
        $commit->getTableName(),
        PhorgeRepository::TABLE_PARENTS,
        $target->getID());
      if (!$rows) {
        continue;
      }

      $parents = id(new PhorgeRepositoryCommit())
        ->loadAllFromArray($rows);
      foreach ($parents as $parent) {
        $look[] = $parent;
      }
    }

    $unreachable = array_reverse($unreachable);

    $flag = PhorgeRepositoryCommit::IMPORTED_UNREACHABLE;
    foreach ($unreachable as $unreachable_commit) {
      $unreachable_commit->writeImportStatusFlag($flag);
    }

    // If anything was unreachable, just rebuild the whole summary table.
    // We can't really update it incrementally when a commit becomes
    // unreachable.
    if ($unreachable) {
      $this->rebuildSummaryTable($repository);
    }
  }

  private function rebuildSummaryTable(PhorgeRepository $repository) {
    $conn_w = $repository->establishConnection('w');

    $data = queryfx_one(
      $conn_w,
      'SELECT COUNT(*) N, MAX(id) id, MAX(epoch) epoch
        FROM %T WHERE repositoryID = %d AND (importStatus & %d) != %d',
      id(new PhorgeRepositoryCommit())->getTableName(),
      $repository->getID(),
      PhorgeRepositoryCommit::IMPORTED_UNREACHABLE,
      PhorgeRepositoryCommit::IMPORTED_UNREACHABLE);

    queryfx(
      $conn_w,
      'INSERT INTO %T (repositoryID, size, lastCommitID, epoch)
        VALUES (%d, %d, %d, %d)
        ON DUPLICATE KEY UPDATE
          size = VALUES(size),
          lastCommitID = VALUES(lastCommitID),
          epoch = VALUES(epoch)',
      PhorgeRepository::TABLE_SUMMARY,
      $repository->getID(),
      $data['N'],
      $data['id'],
      $data['epoch']);
  }

}
