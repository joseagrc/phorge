<?php

final class PhorgeWorkerManagementFloodWorkflow
  extends PhorgeWorkerManagementWorkflow {

  protected function didConstruct() {
    $this
      ->setName('flood')
      ->setExamples('**flood**')
      ->setSynopsis(
        pht(
          'Flood the queue with test tasks. This command is intended for '.
          'use during development and debugging.'))
      ->setArguments(
        array(
          array(
            'name' => 'duration',
            'param' => 'seconds',
            'help' => pht(
              'Queue tasks which require a specific amount of wall time to '.
              'complete. By default, tasks complete as quickly as possible.'),
            'default' => 0,
          ),
        ));
  }

  public function execute(PhutilArgumentParser $args) {
    $console = PhutilConsole::getConsole();

    $duration = (float)$args->getArg('duration');

    $console->writeOut(
      "%s\n",
      pht('Adding many test tasks to worker queue. Use ^C to exit.'));

    $n = 0;
    while (true) {
      PhorgeWorker::scheduleTask(
        'PhorgeTestWorker',
        array(
          'duration' => $duration,
        ));

      if (($n++ % 100) === 0) {
        $console->writeOut('.');
      }
    }
  }

}
