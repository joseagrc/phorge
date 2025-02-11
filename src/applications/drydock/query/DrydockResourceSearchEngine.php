<?php

final class DrydockResourceSearchEngine
  extends PhorgeApplicationSearchEngine {

  private $blueprint;

  public function setBlueprint(DrydockBlueprint $blueprint) {
    $this->blueprint = $blueprint;
    return $this;
  }

  public function getBlueprint() {
    return $this->blueprint;
  }

  public function getResultTypeDescription() {
    return pht('Drydock Resources');
  }

  public function getApplicationClassName() {
    return 'PhorgeDrydockApplication';
  }

  public function newQuery() {
    $query = new DrydockResourceQuery();

    $blueprint = $this->getBlueprint();
    if ($blueprint) {
      $query->withBlueprintPHIDs(array($blueprint->getPHID()));
    }

    return $query;
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['statuses']) {
      $query->withStatuses($map['statuses']);
    }

    if ($map['blueprintPHIDs']) {
      $query->withBlueprintPHIDs($map['blueprintPHIDs']);
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhorgeSearchCheckboxesField())
        ->setLabel(pht('Statuses'))
        ->setKey('statuses')
        ->setOptions(DrydockResourceStatus::getStatusMap()),
      id(new PhorgePHIDsSearchField())
        ->setLabel(pht('Blueprints'))
        ->setKey('blueprintPHIDs')
        ->setAliases(array('blueprintPHID', 'blueprints', 'blueprint'))
        ->setDescription(
          pht('Search for resources generated by particular blueprints.')),
    );
  }

  protected function getURI($path) {
    $blueprint = $this->getBlueprint();
    if ($blueprint) {
      $id = $blueprint->getID();
      return "/drydock/blueprint/{$id}/resources/".$path;
    } else {
      return '/drydock/resource/'.$path;
    }
  }

  protected function getBuiltinQueryNames() {
    return array(
      'active' => pht('Active Resources'),
      'all' => pht('All Resources'),
    );
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'active':
        return $query->setParameter(
          'statuses',
          array(
            DrydockResourceStatus::STATUS_PENDING,
            DrydockResourceStatus::STATUS_ACTIVE,
          ));
      case 'all':
        return $query;
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function renderResultList(
    array $resources,
    PhorgeSavedQuery $query,
    array $handles) {

    $list = id(new DrydockResourceListView())
      ->setUser($this->requireViewer())
      ->setResources($resources);

    $result = new PhorgeApplicationSearchResultView();
    $result->setTable($list);

    return $result;
  }

}
