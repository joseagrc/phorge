<?php

final class PhorgeFlagEditController extends PhorgeFlagController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $phid = $request->getURIData('phid');

    $handle = id(new PhorgeHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();

    if (!$handle->isComplete()) {
      return new Aphront404Response();
    }

    $flag = PhorgeFlagQuery::loadUserFlag($viewer, $phid);

    if (!$flag) {
      $flag = new PhorgeFlag();
      $flag->setOwnerPHID($viewer->getPHID());
      $flag->setType($handle->getType());
      $flag->setObjectPHID($handle->getPHID());
      $flag->setReasonPHID($viewer->getPHID());
    }

    if ($request->isDialogFormPost()) {
      $flag->setColor($request->getInt('color'));
      $flag->setNote($request->getStr('note'));
      $flag->save();

      return id(new AphrontReloadResponse())->setURI('/flag/');
    }

    $type_name = $handle->getTypeName();

    $dialog = new AphrontDialogView();
    $dialog->setUser($viewer);

    $dialog->setTitle(pht('Flag %s', $type_name));

    require_celerity_resource('phorge-flag-css');

    $form = new PHUIFormLayoutView();

    $is_new = !$flag->getID();

    if ($is_new) {
      $form
        ->appendChild(hsprintf(
          '<p>%s</p><br />',
          pht('You can flag this %s if you want to remember to look '.
            'at it later.',
            $type_name)));
    }

    $radio = new AphrontFormRadioButtonControl();
    foreach (PhorgeFlagColor::getColorNameMap() as $color => $text) {
      $class = 'phorge-flag-radio phorge-flag-color-'.$color;
      $radio->addButton($color, $text, '', $class);
    }

    $form
      ->appendChild(
        $radio
          ->setName('color')
          ->setLabel(pht('Flag Color'))
          ->setValue($flag->getColor()))
      ->appendChild(
        id(new AphrontFormTextAreaControl())
          ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_SHORT)
          ->setName('note')
          ->setLabel(pht('Note'))
          ->setValue($flag->getNote()));

    $dialog->appendChild($form);

    $dialog->addCancelButton($handle->getURI());
    $dialog->addSubmitButton(
      $is_new ? pht('Create Flag') : pht('Save'));

    return id(new AphrontDialogResponse())->setDialog($dialog);
  }

}
