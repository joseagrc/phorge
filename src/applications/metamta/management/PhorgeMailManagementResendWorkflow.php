<?php

final class PhorgeMailManagementResendWorkflow
  extends PhorgeMailManagementWorkflow {

  protected function didConstruct() {
    $this
      ->setName('resend')
      ->setSynopsis(pht('Send mail again.'))
      ->setExamples(
        '**resend** --id 1 --id 2')
      ->setArguments(
        array(
          array(
            'name'    => 'id',
            'param'   => 'id',
            'help'    => pht('Send mail with a given ID again.'),
            'repeat'  => true,
          ),
        ));
  }

  public function execute(PhutilArgumentParser $args) {
    $console = PhutilConsole::getConsole();

    $ids = $args->getArg('id');
    if (!$ids) {
      throw new PhutilArgumentUsageException(
        pht(
          "Use the '%s' flag to specify one or more messages to resend.",
          '--id'));
    }

    $messages = id(new PhorgeMetaMTAMail())->loadAllWhere(
      'id IN (%Ld)',
      $ids);

    if ($ids) {
      $ids = array_fuse($ids);
      $missing = array_diff_key($ids, $messages);
      if ($missing) {
        throw new PhutilArgumentUsageException(
          pht(
            'Some specified messages do not exist: %s',
            implode(', ', array_keys($missing))));
      }
    }

    foreach ($messages as $message) {
      $message->setStatus(PhorgeMailOutboundStatus::STATUS_QUEUE);
      $message->save();

      $mailer_task = PhorgeWorker::scheduleTask(
        'PhorgeMetaMTAWorker',
        $message->getID(),
        array(
          'priority' => PhorgeWorker::PRIORITY_ALERTS,
        ));

      $console->writeOut(
        "%s\n",
        pht(
          'Queued message #%d for resend.',
          $message->getID()));
    }
  }

}
