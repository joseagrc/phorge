<?php

final class PhorgeCalendarEventEditEngine
  extends PhorgeEditEngine {

  const ENGINECONST = 'calendar.event';

  private $rawTransactions;
  private $seriesEditMode = self::MODE_THIS;

  const MODE_THIS = 'this';
  const MODE_FUTURE = 'future';

  public function setSeriesEditMode($series_edit_mode) {
    $this->seriesEditMode = $series_edit_mode;
    return $this;
  }

  public function getSeriesEditMode() {
    return $this->seriesEditMode;
  }

  public function getEngineName() {
    return pht('Calendar Events');
  }

  public function getSummaryHeader() {
    return pht('Configure Calendar Event Forms');
  }

  public function getSummaryText() {
    return pht('Configure how users create and edit events.');
  }

  public function getEngineApplicationClass() {
    return 'PhorgeCalendarApplication';
  }

  protected function newEditableObject() {
    return PhorgeCalendarEvent::initializeNewCalendarEvent(
      $this->getViewer());
  }

  protected function newObjectQuery() {
    return new PhorgeCalendarEventQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Event');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Event: %s', $object->getName());
  }

  protected function getObjectEditShortText($object) {
    return $object->getMonogram();
  }

  protected function getObjectCreateShortText() {
    return pht('Create Event');
  }

  protected function getObjectName() {
    return pht('Event');
  }

  protected function getObjectViewURI($object) {
    return $object->getURI();
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('event/edit/');
  }

  protected function buildCustomEditFields($object) {
    $viewer = $this->getViewer();

    if ($this->getIsCreate()) {
      $invitee_phids = array($viewer->getPHID());
    } else {
      $invitee_phids = $object->getInviteePHIDsForEdit();
    }

    $frequency_map = PhorgeCalendarEvent::getFrequencyMap();
    $frequency_options = ipull($frequency_map, 'label');

    $rrule = $object->newRecurrenceRule();
    if ($rrule) {
      $frequency = $rrule->getFrequency();
    } else {
      $frequency = null;
    }

    // At least for now, just hide "Invitees" when editing all future events.
    // This may eventually deserve a more nuanced approach.
    $is_future = ($this->getSeriesEditMode() == self::MODE_FUTURE);

    $fields = array(
      id(new PhorgeTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setDescription(pht('Name of the event.'))
        ->setIsRequired(true)
        ->setTransactionType(
          PhorgeCalendarEventNameTransaction::TRANSACTIONTYPE)
        ->setConduitDescription(pht('Rename the event.'))
        ->setConduitTypeDescription(pht('New event name.'))
        ->setValue($object->getName()),
      id(new PhorgeBoolEditField())
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setKey('isAllDay')
        ->setOptions(pht('Normal Event'), pht('All Day Event'))
        ->setAsCheckbox(true)
        ->setTransactionType(
          PhorgeCalendarEventAllDayTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Marks this as an all day event.'))
        ->setConduitDescription(pht('Make the event an all day event.'))
        ->setConduitTypeDescription(pht('Mark the event as an all day event.'))
        ->setValue($object->getIsAllDay()),
      id(new PhorgeEpochEditField())
        ->setKey('start')
        ->setLabel(pht('Start'))
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setTransactionType(
          PhorgeCalendarEventStartDateTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Start time of the event.'))
        ->setConduitDescription(pht('Change the start time of the event.'))
        ->setConduitTypeDescription(pht('New event start time.'))
        ->setValue($object->getStartDateTimeEpoch()),
      id(new PhorgeEpochEditField())
        ->setKey('end')
        ->setLabel(pht('End'))
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setTransactionType(
          PhorgeCalendarEventEndDateTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('End time of the event.'))
        ->setConduitDescription(pht('Change the end time of the event.'))
        ->setConduitTypeDescription(pht('New event end time.'))
        ->setValue($object->newEndDateTimeForEdit()->getEpoch()),
      id(new PhorgeBoolEditField())
        ->setKey('cancelled')
        ->setOptions(pht('Active'), pht('Cancelled'))
        ->setLabel(pht('Cancelled'))
        ->setDescription(pht('Cancel the event.'))
        ->setTransactionType(
          PhorgeCalendarEventCancelTransaction::TRANSACTIONTYPE)
        ->setIsFormField(false)
        ->setConduitDescription(pht('Cancel or restore the event.'))
        ->setConduitTypeDescription(pht('True to cancel the event.'))
        ->setValue($object->getIsCancelled()),
      id(new PhorgeUsersEditField())
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setKey('hostPHID')
        ->setAliases(array('host'))
        ->setLabel(pht('Host'))
        ->setDescription(pht('Host of the event.'))
        ->setTransactionType(
          PhorgeCalendarEventHostTransaction::TRANSACTIONTYPE)
        ->setIsFormField(!$this->getIsCreate())
        ->setConduitDescription(pht('Change the host of the event.'))
        ->setConduitTypeDescription(pht('New event host.'))
        ->setSingleValue($object->getHostPHID()),
      id(new PhorgeDatasourceEditField())
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setIsHidden($is_future)
        ->setKey('inviteePHIDs')
        ->setAliases(array('invite', 'invitee', 'invitees', 'inviteePHID'))
        ->setLabel(pht('Invitees'))
        ->setDatasource(new PhorgeMetaMTAMailableDatasource())
        ->setTransactionType(
          PhorgeCalendarEventInviteTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Users invited to the event.'))
        ->setConduitDescription(pht('Change invited users.'))
        ->setConduitTypeDescription(pht('New event invitees.'))
        ->setValue($invitee_phids)
        ->setCommentActionLabel(pht('Change Invitees')),
      id(new PhorgeRemarkupEditField())
        ->setKey('description')
        ->setLabel(pht('Description'))
        ->setDescription(pht('Description of the event.'))
        ->setTransactionType(
          PhorgeCalendarEventDescriptionTransaction::TRANSACTIONTYPE)
        ->setConduitDescription(pht('Update the event description.'))
        ->setConduitTypeDescription(pht('New event description.'))
        ->setValue($object->getDescription()),
      id(new PhorgeIconSetEditField())
        ->setKey('icon')
        ->setLabel(pht('Icon'))
        ->setIconSet(new PhorgeCalendarIconSet())
        ->setTransactionType(
          PhorgeCalendarEventIconTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Event icon.'))
        ->setConduitDescription(pht('Change the event icon.'))
        ->setConduitTypeDescription(pht('New event icon.'))
        ->setValue($object->getIcon()),

      // NOTE: We're being a little sneaky here. This field is hidden and
      // always has the value "true", so it makes the event recurring when you
      // submit a form which contains the field. Then we put the the field on
      // the "recurring" page in the "Make Recurring" dialog to simplify the
      // workflow. This is still normal, explicit field from the perspective
      // of the API.

      id(new PhorgeBoolEditField())
        ->setIsHidden(true)
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setKey('isRecurring')
        ->setLabel(pht('Recurring'))
        ->setOptions(pht('One-Time Event'), pht('Recurring Event'))
        ->setTransactionType(
          PhorgeCalendarEventRecurringTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('One time or recurring event.'))
        ->setConduitDescription(pht('Make the event recurring.'))
        ->setConduitTypeDescription(pht('Mark the event as a recurring event.'))
        ->setValue(true),
      id(new PhorgeSelectEditField())
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setKey('frequency')
        ->setLabel(pht('Frequency'))
        ->setOptions($frequency_options)
        ->setTransactionType(
          PhorgeCalendarEventFrequencyTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Recurring event frequency.'))
        ->setConduitDescription(pht('Change the event frequency.'))
        ->setConduitTypeDescription(pht('New event frequency.'))
        ->setValue($frequency),
      id(new PhorgeEpochEditField())
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setAllowNull(true)
        ->setHideTime($object->getIsAllDay())
        ->setKey('until')
        ->setLabel(pht('Repeat Until'))
        ->setTransactionType(
          PhorgeCalendarEventUntilDateTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Last instance of the event.'))
        ->setConduitDescription(pht('Change when the event repeats until.'))
        ->setConduitTypeDescription(pht('New final event time.'))
        ->setValue($object->getUntilDateTimeEpoch()),
    );

    return $fields;
  }

  protected function willBuildEditForm($object, array $fields) {
    $all_day_field = idx($fields, 'isAllDay');
    $start_field = idx($fields, 'start');
    $end_field = idx($fields, 'end');

    if ($all_day_field) {
      $is_all_day = $all_day_field->getValueForTransaction();

      $control_ids = array();
      if ($start_field) {
        $control_ids[] = $start_field->getControlID();
      }
      if ($end_field) {
        $control_ids[] = $end_field->getControlID();
      }

      Javelin::initBehavior(
        'event-all-day',
        array(
          'allDayID' => $all_day_field->getControlID(),
          'controlIDs' => $control_ids,
        ));

    } else {
      $is_all_day = $object->getIsAllDay();
    }

    if ($is_all_day) {
      if ($start_field) {
        $start_field->setHideTime(true);
      }

      if ($end_field) {
        $end_field->setHideTime(true);
      }
    }

    return $fields;
  }

  protected function newPages($object) {
    // Controls for event recurrence behavior go on a separate page which we
    // put in a dialog. This simplifies event creation in the common case.

    return array(
      id(new PhorgeEditPage())
        ->setKey('core')
        ->setLabel(pht('Core'))
        ->setIsDefault(true),
      id(new PhorgeEditPage())
        ->setKey('recurring')
        ->setLabel(pht('Recurrence'))
        ->setFieldKeys(
          array(
            'isRecurring',
            'frequency',
            'until',
          )),
    );
  }

  protected function willApplyTransactions($object, array $xactions) {
    $viewer = $this->getViewer();

    $is_parent = $object->isParentEvent();
    $is_child = $object->isChildEvent();
    $is_future = ($this->getSeriesEditMode() === self::MODE_FUTURE);

    // Figure out which transactions we can apply to the whole series of events.
    // Some transactions (like comments) can never be bulk applied.
    $inherited_xactions = array();
    foreach ($xactions as $xaction) {
      $modular_type = $xaction->getModularType();
      if (!($modular_type instanceof PhorgeCalendarEventTransactionType)) {
        continue;
      }

      $inherited_edit = $modular_type->isInheritedEdit();
      if ($inherited_edit) {
        $inherited_xactions[] = $xaction;
      }
    }
    $this->rawTransactions = $this->cloneTransactions($inherited_xactions);

    $must_fork = ($is_child && $is_future) ||
                 ($is_parent && !$is_future);

    // We don't need to fork when editing a parent event if none of the edits
    // can transfer to child events. For example, commenting on a parent is
    // fine.
    if ($is_parent && !$is_future) {
      if (!$inherited_xactions) {
        $must_fork = false;
      }
    }

    if ($must_fork) {
      $fork_target = $object->loadForkTarget($viewer);
      if ($fork_target) {
        $fork_xaction = id(new PhorgeCalendarEventTransaction())
          ->setTransactionType(
            PhorgeCalendarEventForkTransaction::TRANSACTIONTYPE)
          ->setNewValue(true);

        if ($fork_target->getPHID() == $object->getPHID()) {
          // We're forking the object itself, so just slip it into the
          // transactions we're going to apply.
          array_unshift($xactions, $fork_xaction);
        } else {
          // Otherwise, we're forking a different object, so we have to
          // apply that separately.
          $this->applyTransactions($fork_target, array($fork_xaction));
        }
      }
    }

    return $xactions;
  }

  protected function didApplyTransactions($object, array $xactions) {
    $viewer = $this->getViewer();

    if ($this->getSeriesEditMode() !== self::MODE_FUTURE) {
      return;
    }

    $targets = $object->loadFutureEvents($viewer);
    if (!$targets) {
      return;
    }

    foreach ($targets as $target) {
      $apply = $this->cloneTransactions($this->rawTransactions);
      $this->applyTransactions($target, $apply);
    }
  }

  private function applyTransactions($target, array $xactions) {
    $viewer = $this->getViewer();

    // TODO: This isn't the most accurate source we could use, but this mode
    // is web-only for now.
    $content_source = PhorgeContentSource::newForSource(
      PhorgeWebContentSource::SOURCECONST);

    $editor = id(new PhorgeCalendarEventEditor())
      ->setActor($viewer)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true)
      ->setContinueOnMissingFields(true);

    try {
      $editor->applyTransactions($target, $xactions);
    } catch (PhorgeApplicationTransactionValidationException $ex) {
      // Just ignore any issues we run into.
    }
  }

  private function cloneTransactions(array $xactions) {
    $result = array();
    foreach ($xactions as $xaction) {
      $result[] = clone $xaction;
    }
    return $result;
  }

}
