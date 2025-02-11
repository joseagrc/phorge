<?php

final class PhorgeFaviconController
  extends PhorgeController {

  public function shouldRequireLogin() {
    return false;
  }

  public function handleRequest(AphrontRequest $request) {
    // See PHI1719. Phorge uses "<link /"> tags in the document body
    // to direct user agents to icons, like this:
    //
    //   <link rel="icon" href="..." />
    //
    // However, some software requests the hard-coded path "/favicon.ico"
    // directly. To tidy the logs, serve some reasonable response rather than
    // a 404.

    // NOTE: Right now, this only works for the "PhorgePlatformSite".
    // Other sites (like custom Phame blogs) won't currently route this
    // path.

    $ref = id(new PhorgeFaviconRef())
      ->setWidth(64)
      ->setHeight(64);

    id(new PhorgeFaviconRefQuery())
      ->withRefs(array($ref))
      ->execute();

    return id(new AphrontRedirectResponse())
      ->setIsExternal(true)
      ->setURI($ref->getURI());
  }
}
