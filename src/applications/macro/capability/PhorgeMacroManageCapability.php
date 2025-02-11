<?php

final class PhorgeMacroManageCapability
  extends PhorgePolicyCapability {

  const CAPABILITY = 'macro.manage';

  public function getCapabilityName() {
    return pht('Can Manage Macros');
  }

  public function describeCapabilityRejection() {
    return pht('You do not have permission to manage image macros.');
  }

}
