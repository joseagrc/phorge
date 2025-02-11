<?php

final class PhorgeLunarPhasePolicyRule extends PhorgePolicyRule {

  const PHASE_FULL   = 'full';
  const PHASE_NEW    = 'new';
  const PHASE_WAXING = 'waxing';
  const PHASE_WANING = 'waning';

  public function getRuleDescription() {
    return pht('when the moon');
  }

  public function applyRule(
    PhorgeUser $viewer,
    $value,
    PhorgePolicyInterface $object) {

    $moon = new PhutilLunarPhase(PhorgeTime::getNow());

    switch ($value) {
      case 'full':
        return $moon->isFull();
      case 'new':
        return $moon->isNew();
      case 'waxing':
        return $moon->isWaxing();
      case 'waning':
        return $moon->isWaning();
      default:
        return false;
    }
  }

  public function getValueControlType() {
    return self::CONTROL_TYPE_SELECT;
  }

  public function getValueControlTemplate() {
    return array(
      'options' => array(
        self::PHASE_FULL   => pht('is full'),
        self::PHASE_NEW    => pht('is new'),
        self::PHASE_WAXING => pht('is waxing'),
        self::PHASE_WANING => pht('is waning'),
      ),
    );
  }

  public function getRuleOrder() {
    return 1000;
  }

}
