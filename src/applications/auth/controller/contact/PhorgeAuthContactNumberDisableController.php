<?php

final class PhorgeAuthContactNumberDisableController
  extends PhorgeAuthContactNumberController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $number = id(new PhorgeAuthContactNumberQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$number) {
      return new Aphront404Response();
    }

    $is_disable = ($request->getURIData('action') == 'disable');
    $id = $number->getID();
    $cancel_uri = $number->getURI();

    if ($request->isFormOrHisecPost()) {
      $xactions = array();

      if ($is_disable) {
        $new_status = PhorgeAuthContactNumber::STATUS_DISABLED;
      } else {
        $new_status = PhorgeAuthContactNumber::STATUS_ACTIVE;
      }

      $xactions[] = id(new PhorgeAuthContactNumberTransaction())
        ->setTransactionType(
          PhorgeAuthContactNumberStatusTransaction::TRANSACTIONTYPE)
        ->setNewValue($new_status);

      $editor = id(new PhorgeAuthContactNumberEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->setCancelURI($cancel_uri);

      try {
        $editor->applyTransactions($number, $xactions);
      } catch (PhorgeApplicationTransactionValidationException $ex) {
        // This happens when you enable a number which collides with another
        // number.
        return $this->newDialog()
          ->setTitle(pht('Changing Status Failed'))
          ->setValidationException($ex)
          ->addCancelButton($cancel_uri);
      }

      return id(new AphrontRedirectResponse())->setURI($cancel_uri);
    }

    $number_display = phutil_tag(
      'strong',
      array(),
      $number->getDisplayName());

    if ($is_disable) {
      $title = pht('Disable Contact Number');
      $body = pht(
        'Disable the contact number %s?',
        $number_display);
      $button = pht('Disable Number');
    } else {
      $title = pht('Enable Contact Number');
      $body = pht(
        'Enable the contact number %s?',
        $number_display);
      $button = pht('Enable Number');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->appendParagraph($body)
      ->addSubmitButton($button)
      ->addCancelButton($cancel_uri);
  }

}
