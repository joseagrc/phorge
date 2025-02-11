<?php

final class PhorgeBadgesArchiveController
  extends PhorgeBadgesController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $badge = id(new PhorgeBadgesQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$badge) {
      return new Aphront404Response();
    }

    $view_uri = $this->getApplicationURI('view/'.$badge->getID().'/');

    if ($request->isFormPost()) {
      if ($badge->isArchived()) {
        $new_status = PhorgeBadgesBadge::STATUS_ACTIVE;
      } else {
        $new_status = PhorgeBadgesBadge::STATUS_ARCHIVED;
      }

      $xactions = array();

      $xactions[] = id(new PhorgeBadgesTransaction())
        ->setTransactionType(
          PhorgeBadgesBadgeStatusTransaction::TRANSACTIONTYPE)
        ->setNewValue($new_status);

      id(new PhorgeBadgesEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($badge, $xactions);

      return id(new AphrontRedirectResponse())->setURI($view_uri);
    }

    if ($badge->isArchived()) {
      $title = pht('Activate Badge');
      $body = pht('This badge will be re-commissioned into service.');
      $button = pht('Activate Badge');
    } else {
      $title = pht('Archive Badge');
      $body = pht(
        'This dedicated badge, once a distinguish icon of this install, '.
        'shall be immediately retired from service, but will never far from '.
        'our hearts. Godspeed.');
      $button = pht('Archive Badge');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->appendChild($body)
      ->addCancelButton($view_uri)
      ->addSubmitButton($button);
  }

}
