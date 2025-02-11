<?php

final class PhorgeCountdownViewController
  extends PhorgeCountdownController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $countdown = id(new PhorgeCountdownQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$countdown) {
      return new Aphront404Response();
    }

    $countdown_view = id(new PhorgeCountdownView())
      ->setUser($viewer)
      ->setCountdown($countdown);

    $id = $countdown->getID();
    $title = $countdown->getTitle();

    $crumbs = $this
      ->buildApplicationCrumbs()
      ->addTextCrumb($countdown->getMonogram())
      ->setBorder(true);

    $epoch = $countdown->getEpoch();
    if ($epoch >= PhorgeTime::getNow()) {
      $icon = 'fa-clock-o';
      $color = '';
      $status = pht('Running');
    } else {
      $icon = 'fa-check-square-o';
      $color = 'dark';
      $status = pht('Launched');
    }

    $header = id(new PHUIHeaderView())
      ->setHeader($title)
      ->setUser($viewer)
      ->setPolicyObject($countdown)
      ->setStatus($icon, $color, $status)
      ->setHeaderIcon('fa-rocket');

    $curtain = $this->buildCurtain($countdown);
    $subheader = $this->buildSubheaderView($countdown);

    $timeline = $this->buildTransactionTimeline(
      $countdown,
      new PhorgeCountdownTransactionQuery());

    $comment_view = id(new PhorgeCountdownEditEngine())
      ->setViewer($viewer)
      ->buildEditEngineCommentView($countdown);

    $content = array(
      $countdown_view,
      $timeline,
      $comment_view,
    );

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setSubheader($subheader)
      ->setCurtain($curtain)
      ->setMainColumn($content);

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(
        array(
          $countdown->getPHID(),
        ))
      ->appendChild($view);
  }

  private function buildCurtain(PhorgeCountdown $countdown) {
    $viewer = $this->getViewer();

    $id = $countdown->getID();

    $can_edit = PhorgePolicyFilter::hasCapability(
      $viewer,
      $countdown,
      PhorgePolicyCapability::CAN_EDIT);

    $curtain = $this->newCurtainView($countdown);

    $curtain->addAction(
      id(new PhorgeActionView())
        ->setIcon('fa-pencil')
        ->setName(pht('Edit Countdown'))
        ->setHref($this->getApplicationURI("edit/{$id}/"))
        ->setDisabled(!$can_edit)
        ->setWorkflow(!$can_edit));

    return $curtain;
  }

  private function buildSubheaderView(
    PhorgeCountdown $countdown) {
    $viewer = $this->getViewer();

    $author = $viewer->renderHandle($countdown->getAuthorPHID())->render();
    $date = phorge_datetime($countdown->getDateCreated(), $viewer);
    $author = phutil_tag('strong', array(), $author);

    $person = id(new PhorgePeopleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($countdown->getAuthorPHID()))
      ->needProfileImage(true)
      ->executeOne();

    $image_uri = $person->getProfileImageURI();
    $image_href = '/p/'.$person->getUsername();

    $content = pht('Authored by %s on %s.', $author, $date);

    return id(new PHUIHeadThingView())
      ->setImage($image_uri)
      ->setImageHref($image_href)
      ->setContent($content);
  }

}
