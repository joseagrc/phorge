<?php

/**
 * Redirect to the current raw contents of a Paste.
 *
 * This controller provides a stable URI for getting the current contents of
 * a paste, and slightly simplifies the view controller.
 */
final class PhorgePasteRawController
  extends PhorgePasteController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $paste = id(new PhorgePasteQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$paste) {
      return new Aphront404Response();
    }

    $file = id(new PhorgeFileQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($paste->getFilePHID()))
      ->executeOne();
    if (!$file) {
      return new Aphront400Response();
    }

    return $file->getRedirectResponse();
  }

}
