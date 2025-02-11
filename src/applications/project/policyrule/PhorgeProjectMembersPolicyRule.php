<?php

final class PhorgeProjectMembersPolicyRule extends PhorgePolicyRule {

  private $memberships = array();

  public function getRuleDescription() {
    return pht('members of project');
  }

  public function willApplyRules(
    PhorgeUser $viewer,
    array $values,
    array $objects) {

    $viewer_phid = $viewer->getPHID();
    if (!$viewer_phid) {
      return;
    }

    if (empty($this->memberships[$viewer_phid])) {
      $this->memberships[$viewer_phid] = array();
    }

    foreach ($objects as $key => $object) {
      $cache = $this->getTransactionHint($object);
      if ($cache === null) {
        continue;
      }

      unset($objects[$key]);

      if (isset($cache[$viewer_phid])) {
        $this->memberships[$viewer_phid][$object->getPHID()] = true;
      }
    }

    if (!$objects) {
      return;
    }

    $object_phids = mpull($objects, 'getPHID');
    $edge_query = id(new PhorgeEdgeQuery())
      ->withSourcePHIDs(array($viewer_phid))
      ->withDestinationPHIDs($object_phids)
      ->withEdgeTypes(
        array(
          PhorgeProjectMemberOfProjectEdgeType::EDGECONST,
        ));
    $edge_query->execute();

    $memberships = $edge_query->getDestinationPHIDs();
    if (!$memberships) {
      return;
    }

    $this->memberships[$viewer_phid] += array_fill_keys($memberships, true);
  }

  public function applyRule(
    PhorgeUser $viewer,
    $value,
    PhorgePolicyInterface $object) {
    $viewer_phid = $viewer->getPHID();
    if (!$viewer_phid) {
      return false;
    }

    $memberships = idx($this->memberships, $viewer_phid);
    return isset($memberships[$object->getPHID()]);
  }

  public function getValueControlType() {
    return self::CONTROL_TYPE_NONE;
  }

  public function canApplyToObject(PhorgePolicyInterface $object) {
    return ($object instanceof PhorgeProject);
  }

  public function getObjectPolicyKey() {
    return 'project.members';
  }

  public function getObjectPolicyName() {
    return pht('Project Members');
  }

  public function getObjectPolicyIcon() {
    return 'fa-users';
  }

  public function getPolicyExplanation() {
    return pht('Project members can take this action.');
  }

}
