<?php

final class PhorgeTokenGiveController extends PhorgeTokenController {

 public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $phid = $request->getURIData('phid');

    $handle = id(new PhorgeHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();
    if (!$handle->isComplete()) {
      return new Aphront404Response();
    }

    $object = id(new PhorgeObjectQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();

    if (!($object instanceof PhorgeTokenReceiverInterface)) {
      return new Aphront400Response();
    }

    if (!PhorgePolicyFilter::canInteract($viewer, $object)) {
      $lock = PhorgeEditEngineLock::newForObject($viewer, $object);

      $dialog = $this->newDialog()
        ->addCancelButton($handle->getURI());

      return $lock->willBlockUserInteractionWithDialog($dialog);
    }

    $current = id(new PhorgeTokenGivenQuery())
      ->setViewer($viewer)
      ->withAuthorPHIDs(array($viewer->getPHID()))
      ->withObjectPHIDs(array($handle->getPHID()))
      ->execute();

    if ($current) {
      $is_give = false;
      $title = pht('Rescind Token');
    } else {
      $is_give = true;
      $title = pht('Give Token');
    }

    $done_uri = $handle->getURI();
    if ($request->isFormOrHisecPost()) {
      $content_source = PhorgeContentSource::newFromRequest($request);

      $editor = id(new PhorgeTokenGivenEditor())
        ->setActor($viewer)
        ->setRequest($request)
        ->setCancelURI($handle->getURI())
        ->setContentSource($content_source);
      if ($is_give) {
        $token_phid = $request->getStr('tokenPHID');
        $editor->addToken($handle->getPHID(), $token_phid);
      } else {
        $editor->deleteToken($handle->getPHID());
      }

      return id(new AphrontReloadResponse())->setURI($done_uri);
    }

    if ($is_give) {
      $dialog = $this->buildGiveTokenDialog();
    } else {
      $dialog = $this->buildRescindTokenDialog(head($current));
    }

    $dialog->setUser($viewer);
    $dialog->addCancelButton($done_uri);

    return id(new AphrontDialogResponse())->setDialog($dialog);
  }

  private function buildGiveTokenDialog() {
    $viewer = $this->getViewer();

    $tokens = id(new PhorgeTokenQuery())
      ->setViewer($viewer)
      ->execute();

    $buttons = array();
    $ii = 0;
    foreach ($tokens as $token) {
      $aural = javelin_tag(
        'span',
        array(
          'aural' => true,
        ),
        pht('Award "%s" Token', $token->getName()));

      $buttons[] = javelin_tag(
        'button',
        array(
          'class' => 'token-button',
          'name' => 'tokenPHID',
          'value' => $token->getPHID(),
          'type' => 'submit',
          'sigil' => 'has-tooltip',
          'meta' => array(
            'tip' => $token->getName(),
          ),
        ),
        array(
          $aural,
          $token->renderIcon(),
        ));
      if ((++$ii % 6) == 0) {
        $buttons[] = phutil_tag('br');
      }
    }

    $buttons = phutil_tag(
      'div',
      array(
        'class' => 'token-grid',
      ),
      $buttons);

    $dialog = new AphrontDialogView();
    $dialog->setTitle(pht('Give Token'));
    $dialog->appendChild($buttons);

    return $dialog;
  }

  private function buildRescindTokenDialog(PhorgeTokenGiven $token_given) {
    $dialog = new AphrontDialogView();
    $dialog->setTitle(pht('Rescind Token'));

    $dialog->appendChild(
      pht('Really rescind this lovely token?'));

    $dialog->addSubmitButton(pht('Rescind Token'));

    return $dialog;
  }

}
