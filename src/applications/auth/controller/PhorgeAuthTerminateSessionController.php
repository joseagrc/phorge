<?php

final class PhorgeAuthTerminateSessionController
  extends PhorgeAuthController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');

    $is_all = ($id === 'all');

    $query = id(new PhorgeAuthSessionQuery())
      ->setViewer($viewer)
      ->withIdentityPHIDs(array($viewer->getPHID()));
    if (!$is_all) {
      $query->withIDs(array($id));
    }

    $current_key = PhorgeAuthSession::newSessionDigest(
      new PhutilOpaqueEnvelope(
        $request->getCookie(PhorgeCookies::COOKIE_SESSION)));

    $sessions = $query->execute();
    foreach ($sessions as $key => $session) {
      $is_current = phutil_hashes_are_identical(
        $session->getSessionKey(),
        $current_key);
      if ($is_current) {
        // Don't terminate the current login session.
        unset($sessions[$key]);
      }
    }

    $panel_uri = '/settings/panel/sessions/';

    if (!$sessions) {
      return $this->newDialog()
        ->setTitle(pht('No Matching Sessions'))
        ->appendParagraph(
          pht('There are no matching sessions to terminate.'))
        ->appendParagraph(
          pht(
            '(You can not terminate your current login session. To '.
            'terminate it, log out.)'))
        ->addCancelButton($panel_uri);
    }

    if ($request->isDialogFormPost()) {
      foreach ($sessions as $session) {
        $session->delete();
      }
      return id(new AphrontRedirectResponse())->setURI($panel_uri);
    }

    if ($is_all) {
      $title = pht('Terminate Sessions?');
      $short = pht('Terminate Sessions');
      $body = pht(
        'Really terminate all sessions? (Your current login session will '.
        'not be terminated.)');
    } else {
      $title = pht('Terminate Session?');
      $short = pht('Terminate Session');
      $body = pht(
        'Really terminate session %s?',
        phutil_tag('strong', array(), substr($session->getSessionKey(), 0, 6)));
    }

    return $this->newDialog()
      ->setTitle($title)
      ->setShortTitle($short)
      ->appendParagraph($body)
      ->addSubmitButton(pht('Terminate'))
      ->addCancelButton($panel_uri);
  }


}
