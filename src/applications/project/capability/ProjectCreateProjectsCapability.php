<?php

final class ProjectCreateProjectsCapability
  extends PhorgePolicyCapability {

  const CAPABILITY = 'project.create';

  public function getCapabilityName() {
    return pht('Can Create Projects');
  }

  public function describeCapabilityRejection() {
    return pht('You do not have permission to create new projects.');
  }

}
