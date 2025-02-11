<?php

final class AlmanacCreateServicesCapability
  extends PhorgePolicyCapability {

  const CAPABILITY = 'almanac.services';

  public function getCapabilityName() {
    return pht('Can Create Services');
  }

  public function describeCapabilityRejection() {
    return pht('You do not have permission to create Almanac services.');
  }

}
