<?php

final class PhortunePaymentMethodPolicyCodex
  extends PhorgePolicyCodex {

  public function getPolicySpecialRuleDescriptions() {
    $object = $this->getObject();

    $rules = array();

    $rules[] = $this->newRule()
      ->setCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->setIsActive(true)
      ->setDescription(
        pht(
          'Account members may view and edit payment methods.'));

    $rules[] = $this->newRule()
      ->setCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
        ))
      ->setIsActive(true)
      ->setDescription(
        pht(
          'Merchants you have a relationship with may view associated '.
          'payment methods.'));

    return $rules;
  }

}
