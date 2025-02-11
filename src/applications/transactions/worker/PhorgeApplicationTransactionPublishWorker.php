<?php

/**
 * Performs backgroundable work after applying transactions.
 *
 * This class handles email, notifications, feed stories, search indexes, and
 * other similar backgroundable work.
 */
final class PhorgeApplicationTransactionPublishWorker
  extends PhorgeWorker {


  /**
   * Publish information about a set of transactions.
   */
  protected function doWork() {
    $object = $this->loadObject();
    $editor = $this->buildEditor($object);
    $xactions = $this->loadTransactions($object);

    $editor->publishTransactions($object, $xactions);
  }


  /**
   * Load the object the transactions affect.
   */
  private function loadObject() {
    $viewer = PhorgeUser::getOmnipotentUser();

    $data = $this->getTaskData();
    if (!is_array($data)) {
      throw new PhorgeWorkerPermanentFailureException(
        pht('Task has invalid task data.'));
    }

    $phid = idx($data, 'objectPHID');
    if (!$phid) {
      throw new PhorgeWorkerPermanentFailureException(
        pht('Task has no object PHID!'));
    }

    $object = id(new PhorgeObjectQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();
    if (!$object) {
      throw new PhorgeWorkerPermanentFailureException(
        pht(
          'Unable to load object with PHID "%s"!',
          $phid));
    }

    return $object;
  }


  /**
   * Build and configure an Editor to publish these transactions.
   */
  private function buildEditor(
    PhorgeApplicationTransactionInterface $object) {
    $data = $this->getTaskData();

    $daemon_source = $this->newContentSource();

    $viewer = PhorgeUser::getOmnipotentUser();
    $editor = $object->getApplicationTransactionEditor()
      ->setActor($viewer)
      ->setContentSource($daemon_source)
      ->setActingAsPHID(idx($data, 'actorPHID'))
      ->loadWorkerState(idx($data, 'state', array()));

    return $editor;
  }


  /**
   * Load the transactions to be published.
   */
  private function loadTransactions(
    PhorgeApplicationTransactionInterface $object) {
    $data = $this->getTaskData();

    $xaction_phids = idx($data, 'xactionPHIDs');
    if (!$xaction_phids) {
      // It's okay if we don't have any transactions. This can happen when
      // creating objects or performing no-op updates. We will still apply
      // meaningful side effects like updating search engine indexes.
      return array();
    }

    $viewer = PhorgeUser::getOmnipotentUser();

    $query = PhorgeApplicationTransactionQuery::newQueryForObject($object);
    if (!$query) {
      throw new PhorgeWorkerPermanentFailureException(
        pht(
          'Unable to load query for transaction object "%s"!',
          $object->getPHID()));
    }

    $xactions = $query
      ->setViewer($viewer)
      ->withPHIDs($xaction_phids)
      ->needComments(true)
      ->execute();
    $xactions = mpull($xactions, null, 'getPHID');

    $missing = array_diff($xaction_phids, array_keys($xactions));
    if ($missing) {
      throw new PhorgeWorkerPermanentFailureException(
        pht(
          'Unable to load transactions: %s.',
          implode(', ', $missing)));
    }

    return array_select_keys($xactions, $xaction_phids);
  }

}
