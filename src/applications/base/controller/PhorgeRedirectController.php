<?php

final class PhorgeRedirectController extends PhorgeController {

  public function shouldRequireLogin() {
    return false;
  }

  public function shouldRequireEnabledUser() {
    return false;
  }

  public function handleRequest(AphrontRequest $request) {
    $uri = $request->getURIData('uri');
    $external = $request->getURIData('external', false);
    return id(new AphrontRedirectResponse())
      ->setURI($uri)
      ->setIsExternal($external);
  }

}
