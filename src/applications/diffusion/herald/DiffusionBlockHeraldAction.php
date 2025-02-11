<?php

final class DiffusionBlockHeraldAction
  extends HeraldAction {

  const ACTIONCONST = 'diffusion.block';

  const DO_BLOCK = 'do.block';

  public function getHeraldActionName() {
    return pht('Block push with message');
  }

  public function getActionGroupKey() {
    return HeraldApplicationActionGroup::ACTIONGROUPKEY;
  }

  public function supportsObject($object) {
    return ($object instanceof PhorgeRepositoryPushLog);
  }

  public function supportsRuleType($rule_type) {
    return ($rule_type != HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
  }

  public function applyEffect($object, HeraldEffect $effect) {
    // This rule intentionally has no direct effect: the caller handles it
    // after executing Herald.
    $this->logEffect(self::DO_BLOCK);
  }

  public function getHeraldActionStandardType() {
    return self::STANDARD_TEXT;
  }

  public function renderActionDescription($value) {
    return pht('Block push with message: %s', $value);
  }

  protected function getActionEffectMap() {
    return array(
      self::DO_BLOCK => array(
        'icon' => 'fa-stop',
        'color' => 'red',
        'name' => pht('Blocked Push'),
      ),
    );
  }

  protected function renderActionEffectDescription($type, $data) {
    switch ($type) {
      case self::DO_BLOCK:
        return pht('Blocked push.');
    }
  }
}
