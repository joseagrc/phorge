<?php

final class PhrictionContentSearchEngine
  extends PhorgeApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Phriction Document Content');
  }

  public function getApplicationClassName() {
    return 'PhorgePhrictionApplication';
  }

  public function newQuery() {
    return new PhrictionContentQuery();
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['documentPHIDs']) {
      $query->withDocumentPHIDs($map['documentPHIDs']);
    }

    if ($map['versions']) {
      $query->withVersions($map['versions']);
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhorgePHIDsSearchField())
        ->setKey('documentPHIDs')
        ->setAliases(array('document', 'documents', 'documentPHID'))
        ->setLabel(pht('Documents')),
      id(new PhorgeIDsSearchField())
        ->setKey('versions')
        ->setAliases(array('version')),
    );
  }

  protected function getURI($path) {
    // There's currently no web UI for this search interface, it exists purely
    // to power the Conduit API.
    throw new PhutilMethodNotImplementedException();
  }

  protected function getBuiltinQueryNames() {
    return array(
      'all' => pht('All Content'),
    );
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function renderResultList(
    array $contents,
    PhorgeSavedQuery $query,
    array $handles) {
    assert_instances_of($contents, 'PhrictionContent');
    throw new PhutilMethodNotImplementedException();
  }

}
