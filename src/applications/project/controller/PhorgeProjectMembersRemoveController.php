<?php

final class PhorgeProjectMembersRemoveController
  extends PhorgeProjectController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');
    $type = $request->getURIData('type');

    $project = id(new PhorgeProjectQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needMembers(true)
      ->needWatchers(true)
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$project) {
      return new Aphront404Response();
    }

    if ($type == 'watchers') {
      $is_watcher = true;
      $edge_type = PhorgeObjectHasWatcherEdgeType::EDGECONST;
    } else {
      if (!$project->supportsEditMembers()) {
        return new Aphront404Response();
      }

      $is_watcher = false;
      $edge_type = PhorgeProjectProjectHasMemberEdgeType::EDGECONST;
    }

    $members_uri = $this->getApplicationURI('members/'.$project->getID().'/');
    $remove_phid = $request->getStr('phid');

    if ($request->isFormPost()) {
      $xactions = array();

      $xactions[] = id(new PhorgeProjectTransaction())
        ->setTransactionType(PhorgeTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', $edge_type)
        ->setNewValue(
          array(
            '-' => array($remove_phid => $remove_phid),
          ));

      $editor = id(new PhorgeProjectTransactionEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($project, $xactions);

      return id(new AphrontRedirectResponse())
        ->setURI($members_uri);
    }

    $handle = id(new PhorgeHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($remove_phid))
      ->executeOne();

    $target_name = phutil_tag('strong', array(), $handle->getName());
    $project_name = phutil_tag('strong', array(), $project->getName());

    if ($is_watcher) {
      $title = pht('Remove Watcher');
      $body = pht(
        'Remove %s as a watcher of %s?',
        $target_name,
        $project_name);
      $button = pht('Remove Watcher');
    } else {
      $title = pht('Remove Member');
      $body = pht(
        'Remove %s as a project member of %s?',
        $target_name,
        $project_name);
      $button = pht('Remove Member');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->addHiddenInput('phid', $remove_phid)
      ->appendParagraph($body)
      ->addCancelButton($members_uri)
      ->addSubmitButton($button);
  }

}
