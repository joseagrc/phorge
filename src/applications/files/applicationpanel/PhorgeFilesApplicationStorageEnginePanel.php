<?php

final class PhorgeFilesApplicationStorageEnginePanel
  extends PhorgeApplicationConfigurationPanel {

  public function getPanelKey() {
    return 'storage';
  }

  public function shouldShowForApplication(
    PhorgeApplication $application) {
    return ($application instanceof PhorgeFilesApplication);
  }

  public function buildConfigurationPagePanel() {
    $viewer = $this->getViewer();
    $application = $this->getApplication();

    $engines = PhorgeFileStorageEngine::loadAllEngines();
    $writable_engines = PhorgeFileStorageEngine::loadWritableEngines();
    $chunk_engines = PhorgeFileStorageEngine::loadWritableChunkEngines();

    $yes = pht('Yes');
    $no = pht('No');

    $rows = array();
    $rowc = array();
    foreach ($engines as $key => $engine) {
      if ($engine->isTestEngine()) {
        continue;
      }

      $limit = null;
      if ($engine->hasFilesizeLimit()) {
        $limit = phutil_format_bytes($engine->getFilesizeLimit());
      } else {
        $limit = pht('Unlimited');
      }

      if ($engine->canWriteFiles()) {
        $writable = $yes;
      } else {
        $writable = $no;
      }

      if (isset($writable_engines[$key]) || isset($chunk_engines[$key])) {
        $rowc[] = 'highlighted';
      } else {
        $rowc[] = null;
      }

      $rows[] = array(
        $key,
        get_class($engine),
        $writable,
        $limit,
      );
    }

    $table = id(new AphrontTableView($rows))
      ->setNoDataString(pht('No storage engines available.'))
      ->setHeaders(
        array(
          pht('Key'),
          pht('Class'),
          pht('Writable'),
          pht('Limit'),
        ))
      ->setRowClasses($rowc)
      ->setColumnClasses(
        array(
          '',
          'wide',
          '',
          'n',
        ));

    $box = id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Storage Engines'))
      ->setTable($table);

    return $box;
  }

  public function handlePanelRequest(
    AphrontRequest $request,
    PhorgeController $controller) {
    return new Aphront404Response();
  }

}
