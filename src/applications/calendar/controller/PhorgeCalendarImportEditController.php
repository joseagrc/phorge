<?php

final class PhorgeCalendarImportEditController
  extends PhorgeCalendarController {

  public function handleRequest(AphrontRequest $request) {
    $engine = id(new PhorgeCalendarImportEditEngine())
      ->setController($this);

    $id = $request->getURIData('id');
    if ($id) {

      // edit a specific entry

      $calendar_import = self::queryImportByID($request, $id);
      if (!$calendar_import) {
        return new Aphront404Response();
      }

      // pass the correct import engine to build the response
      $engine->setImportEngine($calendar_import->getEngine());

    } else {

      // create an entry

      $list_uri = $this->getApplicationURI('import/');

      $import_type = $request->getStr('importType');
      $import_engines = PhorgeCalendarImportEngine::getAllImportEngines();
      if (empty($import_engines[$import_type])) {
        return $this->buildEngineTypeResponse($list_uri);
      }

      $import_engine = $import_engines[$import_type];

      $engine
        ->addContextParameter('importType', $import_type)
        ->setImportEngine($import_engine);
    }

    return $engine->buildResponse();
  }

  private static function queryImportByID(AphrontRequest $request, int $id) {
      return id(new PhorgeCalendarImportQuery())
        ->setViewer($request->getViewer())
        ->withIDs(array($id))
        ->requireCapabilities(
          array(
            PhorgePolicyCapability::CAN_VIEW,
            PhorgePolicyCapability::CAN_EDIT,
          ))
        ->executeOne();
  }

  private function buildEngineTypeResponse($cancel_uri) {
    $import_engines = PhorgeCalendarImportEngine::getAllImportEngines();

    $request = $this->getRequest();
    $viewer = $this->getViewer();

    $e_import = null;
    $errors = array();
    if ($request->isFormPost()) {
      $e_import = pht('Required');
      $errors[] = pht(
        'To import events, you must select a source to import from.');
    }

    $type_control = id(new AphrontFormRadioButtonControl())
      ->setLabel(pht('Import Type'))
      ->setName('importType')
      ->setError($e_import);

    foreach ($import_engines as $import_engine) {
      $type_control->addButton(
        $import_engine->getImportEngineType(),
        $import_engine->getImportEngineName(),
        $import_engine->getImportEngineHint());
    }

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('New Import'));
    $crumbs->setBorder(true);

    $title = pht('Choose Import Type');
    $header = id(new PHUIHeaderView())
      ->setHeader(pht('New Import'))
      ->setHeaderIcon('fa-upload');

    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendChild($type_control)
      ->appendChild(
          id(new AphrontFormSubmitControl())
            ->setValue(pht('Continue'))
            ->addCancelButton($cancel_uri));

    $box = id(new PHUIObjectBoxView())
      ->setFormErrors($errors)
      ->setHeaderText(pht('Import'))
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setForm($form);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(
        array(
          $box,
        ));

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild($view);
  }

}
