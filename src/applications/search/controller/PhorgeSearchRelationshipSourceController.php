<?php

final class PhorgeSearchRelationshipSourceController
  extends PhorgeSearchBaseController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $object = $this->loadRelationshipObject();
    if (!$object) {
      return new Aphront404Response();
    }

    $relationship = $this->loadRelationship($object);
    if (!$relationship) {
      return new Aphront404Response();
    }

    $source = $relationship->newSource();
    $query = new PhorgeSavedQuery();

    $action = $request->getURIData('action');
    $query_str = $request->getStr('query');
    $filter = $request->getStr('filter');

    $query->setEngineClassName('PhorgeSearchApplicationSearchEngine');
    $query->setParameter('query', $query_str);

    $types = $source->getResultPHIDTypes();
    $query->setParameter('types', $types);

    $status_open = PhorgeSearchRelationship::RELATIONSHIP_OPEN;

    switch ($filter) {
      case 'assigned':
        $query->setParameter('ownerPHIDs', array($viewer->getPHID()));
        $query->setParameter('statuses', array($status_open));
        break;
      case 'created';
        $query->setParameter('authorPHIDs', array($viewer->getPHID()));
        $query->setParameter('statuses', array($status_open));
        break;
      case 'open':
        $query->setParameter('statuses', array($status_open));
        break;
    }

    $query->setParameter('excludePHIDs', array($request->getStr('exclude')));

    $capabilities = $relationship->getRequiredRelationshipCapabilities();

    $results = id(new PhorgeSearchDocumentQuery())
      ->setViewer($viewer)
      ->requireObjectCapabilities($capabilities)
      ->withSavedQuery($query)
      ->setOffset(0)
      ->setLimit(100)
      ->execute();

    $phids = array_fill_keys(mpull($results, 'getPHID'), true);
    $phids = $this->queryObjectNames($query, $capabilities) + $phids;

    $phids = array_keys($phids);
    $handles = $viewer->loadHandles($phids);

    $data = array();
    foreach ($handles as $handle) {
      $view = new PhorgeHandleObjectSelectorDataView($handle);
      $data[] = $view->renderData();
    }

    return id(new AphrontAjaxResponse())->setContent($data);
  }

  private function queryObjectNames(
    PhorgeSavedQuery $query,
    array $capabilities) {

    $request = $this->getRequest();
    $viewer = $request->getUser();

    $types = $query->getParameter('types');
    $match = $query->getParameter('query');

    $objects = id(new PhorgeObjectQuery())
      ->setViewer($viewer)
      ->requireCapabilities($capabilities)
      ->withTypes($query->getParameter('types'))
      ->withNames(array($match))
      ->execute();

    return mpull($objects, 'getPHID');
  }

}
