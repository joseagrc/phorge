<?php

final class PhorgeAphlictManagementStartWorkflow
  extends PhorgeAphlictManagementWorkflow {

  protected function didConstruct() {
    $this
      ->setName('start')
      ->setSynopsis(pht('Start the notifications server.'))
      ->setArguments($this->getLaunchArguments());
  }

  public function execute(PhutilArgumentParser $args) {
    $this->parseLaunchArguments($args);
    return $this->executeStartCommand();
  }

}
