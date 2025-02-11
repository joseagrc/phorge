<?php

final class PhorgeRepositoryManagementListWorkflow
  extends PhorgeRepositoryManagementWorkflow {

  protected function didConstruct() {
    $this
      ->setName('list')
      ->setSynopsis(pht('Show a list of repositories.'))
      ->setArguments(array());
  }

  public function execute(PhutilArgumentParser $args) {
    $console = PhutilConsole::getConsole();

    $repos = id(new PhorgeRepositoryQuery())
      ->setViewer($this->getViewer())
      ->execute();
    if ($repos) {
      foreach ($repos as $repo) {
        $console->writeOut("%s\n", $repo->getMonogram());
      }
    } else {
      $console->writeErr("%s\n", pht('There are no repositories.'));
    }

    return 0;
  }

}
