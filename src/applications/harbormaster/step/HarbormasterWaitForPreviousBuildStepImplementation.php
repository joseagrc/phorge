<?php

final class HarbormasterWaitForPreviousBuildStepImplementation
  extends HarbormasterBuildStepImplementation {

  public function getName() {
    return pht('Wait for Previous Commits to Build');
  }

  public function getGenericDescription() {
    return pht(
      'Wait for previous commits to finish building the current plan '.
      'before continuing.');
  }

  public function getBuildStepGroupKey() {
    return HarbormasterControlBuildStepGroup::GROUPKEY;
  }

  public function execute(
    HarbormasterBuild $build,
    HarbormasterBuildTarget $build_target) {

    // We can only wait when building against commits.
    $buildable = $build->getBuildable();
    $object = $buildable->getBuildableObject();
    if (!($object instanceof PhorgeRepositoryCommit)) {
      return;
    }

    // Block until all previous builds of the same build plan have
    // finished.
    $plan = $build->getBuildPlan();
    $blockers = $this->getBlockers($object, $plan, $build);

    if ($blockers) {
      throw new PhorgeWorkerYieldException(15);
    }
  }

  private function getBlockers(
    PhorgeRepositoryCommit $commit,
    HarbormasterBuildPlan $plan,
    HarbormasterBuild $source) {

    $call = new ConduitCall(
      'diffusion.commitparentsquery',
      array(
        'commit' => $commit->getCommitIdentifier(),
        'repository' => $commit->getRepository()->getPHID(),
      ));
    $call->setUser(PhorgeUser::getOmnipotentUser());
    $parents = $call->execute();

    $parents = id(new DiffusionCommitQuery())
      ->setViewer(PhorgeUser::getOmnipotentUser())
      ->withRepository($commit->getRepository())
      ->withIdentifiers($parents)
      ->execute();

    $blockers = array();

    $build_objects = array();
    foreach ($parents as $parent) {
      if (!$parent->isImported()) {
        $blockers[] = pht('Commit %s', $parent->getCommitIdentifier());
      } else {
        $build_objects[] = $parent->getPHID();
      }
    }

    if ($build_objects) {
      $buildables = id(new HarbormasterBuildableQuery())
        ->setViewer(PhorgeUser::getOmnipotentUser())
        ->withBuildablePHIDs($build_objects)
        ->withManualBuildables(false)
        ->execute();
      $buildable_phids = mpull($buildables, 'getPHID');

      if ($buildable_phids) {
        $builds = id(new HarbormasterBuildQuery())
          ->setViewer(PhorgeUser::getOmnipotentUser())
          ->withBuildablePHIDs($buildable_phids)
          ->withBuildPlanPHIDs(array($plan->getPHID()))
          ->execute();

        foreach ($builds as $build) {
          if (!$build->isComplete()) {
            $blockers[] = pht('Build %d', $build->getID());
          }
        }
      }
    }

    return $blockers;
  }

}
