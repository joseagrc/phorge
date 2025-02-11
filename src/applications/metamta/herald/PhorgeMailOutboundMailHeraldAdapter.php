<?php

final class PhorgeMailOutboundMailHeraldAdapter
  extends HeraldAdapter {

  private $mail;

  public function getAdapterApplicationClass() {
    return 'PhorgeMetaMTAApplication';
  }

  public function getAdapterContentDescription() {
    return pht('Route outbound email.');
  }

  protected function initializeNewAdapter() {
    $this->mail = $this->newObject();
  }

  protected function newObject() {
    return new PhorgeMetaMTAMail();
  }

  public function isTestAdapterForObject($object) {
    return ($object instanceof PhorgeMetaMTAMail);
  }

  public function getAdapterTestDescription() {
    return pht(
      'Test rules which run when outbound mail is being prepared for '.
      'delivery.');
  }


  public function getObject() {
    return $this->mail;
  }

  public function setObject(PhorgeMetaMTAMail $mail) {
    $this->mail = $mail;
    return $this;
  }

  public function getAdapterContentName() {
    return pht('Outbound Mail');
  }

  public function isSingleEventAdapter() {
    return true;
  }

  public function supportsRuleType($rule_type) {
    switch ($rule_type) {
      case HeraldRuleTypeConfig::RULE_TYPE_GLOBAL:
      case HeraldRuleTypeConfig::RULE_TYPE_PERSONAL:
        return true;
      case HeraldRuleTypeConfig::RULE_TYPE_OBJECT:
      default:
        return false;
    }
  }

  public function getHeraldName() {
    return pht('Mail %d', $this->getObject()->getID());
  }

  public function supportsWebhooks() {
    return false;
  }

}
