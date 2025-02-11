<?php

final class PhorgeSetupEngine
  extends Phobject {

  private $issues;

  public function getIssues() {
    if ($this->issues === null) {
      throw new PhutilInvalidStateException('execute');
    }

    return $this->issues;
  }

  public function getUnresolvedIssues() {
    $issues = $this->getIssues();
    $issues = mpull($issues, null, 'getIssueKey');

    $unresolved_keys = PhorgeSetupCheck::getUnignoredIssueKeys($issues);

    return array_select_keys($issues, $unresolved_keys);
  }

  public function execute() {
    $issues = PhorgeSetupCheck::runNormalChecks();

    $fatal_issue = null;
    foreach ($issues as $issue) {
      if ($issue->getIsFatal()) {
        $fatal_issue = $issue;
        break;
      }
    }

    if ($fatal_issue) {
      // If we've discovered a fatal, we reset any in-flight state to push
      // web hosts out of service.

      // This can happen if Phorge starts during a disaster and some
      // databases can not be reached. We allow Phorge to start up in
      // this situation, since it may still be able to usefully serve requests
      // without risk to data.

      // However, if databases later become reachable and we learn that they
      // are fatally misconfigured, we want to tear the world down again
      // because data may be at risk.
      PhorgeSetupCheck::resetSetupState();

      return PhorgeSetupCheck::newIssueResponse($issue);
    }

    $issue_keys = PhorgeSetupCheck::getUnignoredIssueKeys($issues);

    PhorgeSetupCheck::setOpenSetupIssueKeys(
      $issue_keys,
      $update_database = true);

    $this->issues = $issues;

    return null;
  }

}
