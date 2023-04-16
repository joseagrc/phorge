<?php

final class PhorgeCalendarEventDragController
  extends PhorgeCalendarController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $event = id(new PhorgeCalendarEventQuery())
      ->setViewer($viewer)
      ->withIDs(array($request->getURIData('id')))
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$event) {
      return new Aphront404Response();
    }

    if (!$request->validateCSRF()) {
      return new Aphront400Response();
    }

    if ($event->getIsAllDay()) {
      return new Aphront400Response();
    }

    $xactions = array();

    $duration = $event->getDuration();

    $start = $request->getInt('start');
    $start_value = id(AphrontFormDateControlValue::newFromEpoch(
      $viewer,
      $start));

    $end = $start + $duration;
    $end_value = id(AphrontFormDateControlValue::newFromEpoch(
      $viewer,
      $end));

    $xactions[] = id(new PhorgeCalendarEventTransaction())
      ->setTransactionType(
        PhorgeCalendarEventStartDateTransaction::TRANSACTIONTYPE)
      ->setNewValue($start_value);

    $xactions[] = id(new PhorgeCalendarEventTransaction())
      ->setTransactionType(
        PhorgeCalendarEventEndDateTransaction::TRANSACTIONTYPE)
      ->setNewValue($end_value);

    $editor = id(new PhorgeCalendarEventEditor())
      ->setActor($viewer)
      ->setContinueOnMissingFields(true)
      ->setContentSourceFromRequest($request)
      ->setContinueOnNoEffect(true);

    $xactions = $editor->applyTransactions($event, $xactions);

    return id(new AphrontReloadResponse());
  }
}
