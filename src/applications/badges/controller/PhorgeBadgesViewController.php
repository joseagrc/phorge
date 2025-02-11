<?php

final class PhorgeBadgesViewController
  extends PhorgeBadgesProfileController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $badge = id(new PhorgeBadgesQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$badge) {
      return new Aphront404Response();
    }

    $this->setBadge($badge);

    $crumbs = $this->buildApplicationCrumbs();
    $title = $badge->getName();

    $header = $this->buildHeaderView();
    $curtain = $this->buildCurtain($badge);
    $details = $this->buildDetailsView($badge);

    $timeline = $this->buildTransactionTimeline(
      $badge,
      new PhorgeBadgesTransactionQuery());

    $comment_view = id(new PhorgeBadgesEditEngine())
      ->setViewer($viewer)
      ->buildEditEngineCommentView($badge);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setCurtain($curtain)
      ->setMainColumn(array(
          $timeline,
          $comment_view,
        ))
      ->addPropertySection(pht('Description'), $details);

    $navigation = $this->buildSideNavView('view');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($badge->getPHID()))
      ->setNavigation($navigation)
      ->appendChild($view);
  }

  private function buildDetailsView(
    PhorgeBadgesBadge $badge) {
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer);

    $description = $badge->getDescription();
    if (strlen($description)) {
      $view->addTextContent(
        new PHUIRemarkupView($viewer, $description));
    }

    $badge = id(new PHUIBadgeView())
      ->setIcon($badge->getIcon())
      ->setHeader($badge->getName())
      ->setSubhead($badge->getFlavor())
      ->setQuality($badge->getQuality());

    $view->addTextContent($badge);

    return $view;
  }

  private function buildCurtain(PhorgeBadgesBadge $badge) {
    $viewer = $this->getViewer();

    $can_edit = PhorgePolicyFilter::hasCapability(
      $viewer,
      $badge,
      PhorgePolicyCapability::CAN_EDIT);

    $id = $badge->getID();
    $edit_uri = $this->getApplicationURI("/edit/{$id}/");
    $archive_uri = $this->getApplicationURI("/archive/{$id}/");

    $curtain = $this->newCurtainView($badge);

    $curtain->addAction(
      id(new PhorgeActionView())
        ->setName(pht('Edit Badge'))
        ->setIcon('fa-pencil')
        ->setDisabled(!$can_edit)
        ->setHref($edit_uri));

    if ($badge->isArchived()) {
      $curtain->addAction(
        id(new PhorgeActionView())
          ->setName(pht('Activate Badge'))
          ->setIcon('fa-check')
          ->setDisabled(!$can_edit)
          ->setWorkflow($can_edit)
          ->setHref($archive_uri));
    } else {
      $curtain->addAction(
        id(new PhorgeActionView())
          ->setName(pht('Archive Badge'))
          ->setIcon('fa-ban')
          ->setDisabled(!$can_edit)
          ->setWorkflow($can_edit)
          ->setHref($archive_uri));
    }

    return $curtain;
  }

}
