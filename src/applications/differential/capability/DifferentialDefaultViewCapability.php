<?php

final class DifferentialDefaultViewCapability
  extends PhorgePolicyCapability {

  const CAPABILITY = 'differential.default.view';

  public function getCapabilityName() {
    return pht('Default View Policy');
  }

  public function shouldAllowPublicPolicySetting() {
    return true;
  }

}
