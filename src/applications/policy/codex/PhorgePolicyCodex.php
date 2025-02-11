<?php

/**
 * Rendering extensions that allows an object to render custom strings,
 * descriptions and explanations for the policy system to help users
 * understand complex policies.
 */
abstract class PhorgePolicyCodex
  extends Phobject {

  private $viewer;
  private $object;
  private $policy;
  private $capability;

  public function getPolicyShortName() {
    return null;
  }

  public function getPolicyIcon() {
    return null;
  }

  public function getPolicyTagClasses() {
    return array();
  }

  public function getPolicySpecialRuleDescriptions() {
    return array();
  }

  public function getPolicyForEdit($capability) {
    return $this->getObject()->getPolicy($capability);
  }

  public function getDefaultPolicy() {
    return PhorgePolicyQuery::getDefaultPolicyForObject(
      $this->viewer,
      $this->object,
      $this->capability);
  }

  final protected function newRule() {
    return new PhorgePolicyCodexRuleDescription();
  }

  final public function setViewer(PhorgeUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  final public function getViewer() {
    return $this->viewer;
  }

  final public function setObject(PhorgePolicyCodexInterface $object) {
    $this->object = $object;
    return $this;
  }

  final public function getObject() {
    return $this->object;
  }

  final public function setCapability($capability) {
    $this->capability = $capability;
    return $this;
  }

  final public function getCapability() {
    return $this->capability;
  }

  final public function setPolicy(PhorgePolicy $policy) {
    $this->policy = $policy;
    return $this;
  }

  final public function getPolicy() {
    return $this->policy;
  }

  final public static function newFromObject(
    PhorgePolicyCodexInterface $object,
    PhorgeUser $viewer) {

    if (!($object instanceof PhorgePolicyInterface)) {
      throw new Exception(
        pht(
          'Object (of class "%s") implements interface "%s", but must also '.
          'implement interface "%s".',
          get_class($object),
          'PhorgePolicyCodexInterface',
          'PhorgePolicyInterface'));
    }

    $codex = $object->newPolicyCodex();
    if (!($codex instanceof PhorgePolicyCodex)) {
      throw new Exception(
        pht(
          'Object (of class "%s") implements interface "%s", but defines '.
          'method "%s" incorrectly: this method must return an object of '.
          'class "%s".',
          get_class($object),
          'PhorgePolicyCodexInterface',
          'newPolicyCodex()',
          __CLASS__));
    }

    $codex
      ->setObject($object)
      ->setViewer($viewer);

    return $codex;
  }

}
