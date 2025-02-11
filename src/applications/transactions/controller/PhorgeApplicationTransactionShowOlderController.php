<?php

final class PhorgeApplicationTransactionShowOlderController
  extends PhorgeApplicationTransactionController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();

    $object = id(new PhorgeObjectQuery())
      ->withPHIDs(array($request->getURIData('phid')))
      ->setViewer($viewer)
      ->executeOne();
    if (!$object) {
      return new Aphront404Response();
    }

    if (!$object instanceof PhorgeApplicationTransactionInterface) {
      return new Aphront404Response();
    }

    $query = PhorgeApplicationTransactionQuery::newQueryForObject($object);
    if (!$query) {
      return new Aphront404Response();
    }

    $raw_view_data = $request->getStr('viewData');
    try {
      $view_data = phutil_json_decode($raw_view_data);
    } catch (Exception $ex) {
      $view_data = array();
    }

    $timeline = $this->buildTransactionTimeline(
      $object,
      $query,
      null,
      $view_data);

    $phui_timeline = $timeline->buildPHUITimelineView($with_hiding = false);
    $phui_timeline->setShouldAddSpacers(false);
    $events = $phui_timeline->buildEvents();

    return id(new AphrontAjaxResponse())
      ->setContent(array(
        'timeline' => hsprintf('%s', $events),
      ));
  }

}
