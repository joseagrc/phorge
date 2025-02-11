<?php

final class PhorgeAuthTemporaryTokenTypeModule
  extends PhorgeConfigModule {

  public function getModuleKey() {
    return 'temporarytoken';
  }

  public function getModuleName() {
    return pht('Temporary Token Types');
  }

  public function renderModuleStatus(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $types = PhorgeAuthTemporaryTokenType::getAllTypes();

    $rows = array();
    foreach ($types as $type) {
      $rows[] = array(
        get_class($type),
        $type->getTokenTypeConstant(),
        $type->getTokenTypeDisplayName(),
      );
    }

    return id(new AphrontTableView($rows))
      ->setHeaders(
        array(
          pht('Class'),
          pht('Key'),
          pht('Name'),
        ))
      ->setColumnClasses(
        array(
          null,
          null,
          'wide pri',
        ));

  }

}
