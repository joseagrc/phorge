<?php

final class PhorgeWorkerBulkJobSearchEngine
  extends PhorgeApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Daemon Bulk Jobs');
  }

  public function getApplicationClassName() {
    return 'PhorgeDaemonsApplication';
  }

  public function newQuery() {
    return id(new PhorgeWorkerBulkJobQuery());
  }

  public function canUseInPanelContext() {
    return false;
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['authorPHIDs']) {
      $query->withAuthorPHIDs($map['authorPHIDs']);
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhorgeUsersSearchField())
        ->setLabel(pht('Authors'))
        ->setKey('authorPHIDs')
        ->setAliases(array('author', 'authors')),
    );
  }

  protected function getURI($path) {
    return '/daemon/bulk/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array();

    if ($this->requireViewer()->isLoggedIn()) {
      $names['authored'] = pht('Authored Jobs');
    }

    $names['all'] = pht('All Jobs');

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {

    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
      case 'authored':
        return $query->setParameter(
          'authorPHIDs',
          array($this->requireViewer()->getPHID()));
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function renderResultList(
    array $jobs,
    PhorgeSavedQuery $query,
    array $handles) {
    assert_instances_of($jobs, 'PhorgeWorkerBulkJob');

    $viewer = $this->requireViewer();

    $list = id(new PHUIObjectItemListView())
      ->setUser($viewer);
    foreach ($jobs as $job) {
      $size = pht('%s Bulk Task(s)', new PhutilNumber($job->getSize()));

      $item = id(new PHUIObjectItemView())
        ->setObjectName(pht('Bulk Job %d', $job->getID()))
        ->setHeader($job->getJobName())
        ->addAttribute(phorge_datetime($job->getDateCreated(), $viewer))
        ->setHref($job->getManageURI())
        ->addIcon($job->getStatusIcon(), $job->getStatusName())
        ->addIcon('none', $size);

      $list->addItem($item);
    }

    return id(new PhorgeApplicationSearchResultView())
      ->setContent($list);
  }
}
