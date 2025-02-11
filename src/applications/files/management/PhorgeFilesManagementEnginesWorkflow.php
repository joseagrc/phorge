<?php

final class PhorgeFilesManagementEnginesWorkflow
  extends PhorgeFilesManagementWorkflow {

  protected function didConstruct() {
    $this
      ->setName('engines')
      ->setSynopsis(pht('List available storage engines.'))
      ->setArguments(array());
  }

  public function execute(PhutilArgumentParser $args) {
    $console = PhutilConsole::getConsole();

    $engines = PhorgeFile::buildAllEngines();
    if (!$engines) {
      throw new Exception(pht('No storage engines are available.'));
    }

    foreach ($engines as $engine) {
      $console->writeOut(
        "%s\n",
        $engine->getEngineIdentifier());
    }

    return 0;
  }

}
