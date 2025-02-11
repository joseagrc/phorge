<?php

final class PhorgeProjectColumnRemoveTriggerController
  extends PhorgeProjectBoardController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $column = id(new PhorgeProjectColumnQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$column) {
      return new Aphront404Response();
    }

    $done_uri = $column->getWorkboardURI();

    if (!$column->getTriggerPHID()) {
      return $this->newDialog()
        ->setTitle(pht('No Trigger'))
        ->appendParagraph(
          pht('This column does not have a trigger.'))
        ->addCancelButton($done_uri);
    }

    if ($request->isFormPost()) {
      $column_xactions = array();

      $column_xactions[] = $column->getApplicationTransactionTemplate()
        ->setTransactionType(
          PhorgeProjectColumnTriggerTransaction::TRANSACTIONTYPE)
        ->setNewValue(null);

      $column_editor = $column->getApplicationTransactionEditor()
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true);

      $column_editor->applyTransactions($column, $column_xactions);

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    $body = pht('Really remove the trigger from this column?');

    return $this->newDialog()
      ->setTitle(pht('Remove Trigger'))
      ->appendParagraph($body)
      ->addSubmitButton(pht('Remove Trigger'))
      ->addCancelButton($done_uri);
  }
}
