<?php

final class PhorgeEditEngineBulkJobType
   extends PhorgeWorkerBulkJobType {

  public function getBulkJobTypeKey() {
    return 'transaction.edit';
  }

  public function getJobName(PhorgeWorkerBulkJob $job) {
    return pht('Bulk Edit');
  }

  public function getDescriptionForConfirm(PhorgeWorkerBulkJob $job) {
    $parts = array();

    $parts[] = pht(
      'You are about to apply a bulk edit which will affect '.
      '%s object(s).',
      new PhutilNumber($job->getSize()));

    if ($job->getIsSilent()) {
      $parts[] = pht(
        'If you start work now, this edit will be applied silently: it will '.
        'not send mail or publish notifications.');
    } else {
      $parts[] = pht(
        'If you start work now, this edit will send mail and publish '.
        'notifications normally.');

      $parts[] = pht('To silence this edit, run this command:');

      $command = csprintf(
        'phorge/ $ ./bin/bulk make-silent --id %R',
        $job->getID());
      $command = (string)$command;

      $parts[] = phutil_tag('tt', array(), $command);

      $parts[] = pht(
        'After running this command, reload this page to see the new setting.');
    }

    return $parts;
  }

  public function getJobSize(PhorgeWorkerBulkJob $job) {
    return count($job->getParameter('objectPHIDs', array()));
  }

  public function getDoneURI(PhorgeWorkerBulkJob $job) {
    return $job->getParameter('doneURI');
  }

  public function createTasks(PhorgeWorkerBulkJob $job) {
    $tasks = array();

    foreach ($job->getParameter('objectPHIDs', array()) as $phid) {
      $tasks[] = PhorgeWorkerBulkTask::initializeNewTask($job, $phid);
    }

    return $tasks;
  }

  public function runTask(
    PhorgeUser $actor,
    PhorgeWorkerBulkJob $job,
    PhorgeWorkerBulkTask $task) {

    $object = id(new PhorgeObjectQuery())
      ->setViewer($actor)
      ->withPHIDs(array($task->getObjectPHID()))
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$object) {
      return;
    }

    $raw_xactions = $job->getParameter('xactions');
    $xactions = $this->buildTransactions($object, $raw_xactions);
    $is_silent = $job->getIsSilent();

    $editor = $object->getApplicationTransactionEditor()
      ->setActor($actor)
      ->setContentSource($job->newContentSource())
      ->setContinueOnNoEffect(true)
      ->setContinueOnMissingFields(true)
      ->setIsSilent($is_silent)
      ->applyTransactions($object, $xactions);
  }

  private function buildTransactions($object, array $raw_xactions) {
    $xactions = array();

    foreach ($raw_xactions as $raw_xaction) {
      $xaction = $object->getApplicationTransactionTemplate()
        ->setTransactionType($raw_xaction['type']);

      if (isset($raw_xaction['new'])) {
        $xaction->setNewValue($raw_xaction['new']);
      }

      if (isset($raw_xaction['comment'])) {
        $comment = $xaction->getApplicationTransactionCommentObject()
          ->setContent($raw_xaction['comment']);
        $xaction->attachComment($comment);
      }

      if (isset($raw_xaction['metadata'])) {
        foreach ($raw_xaction['metadata'] as $meta_key => $meta_value) {
          $xaction->setMetadataValue($meta_key, $meta_value);
        }
      }

      if (array_key_exists('old', $raw_xaction)) {
        $xaction->setOldValue($raw_xaction['old']);
      }

      $xactions[] = $xaction;
    }

    return $xactions;
  }

}
