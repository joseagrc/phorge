<?php

final class PhorgePeopleRenameController
  extends PhorgePeopleController {

  public function shouldRequireAdmin() {
    return false;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();

    $id = $request->getURIData('id');

    $user = id(new PhorgePeopleQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$user) {
      return new Aphront404Response();
    }

    $done_uri = $this->getApplicationURI("manage/{$id}/");

    if (!$viewer->getIsAdmin()) {
      $dialog = $this->newDialog()
        ->setTitle(pht('Change Username'))
        ->appendParagraph(
          pht(
            'You can not change usernames because you are not an '.
            'administrator. Only administrators can change usernames.'))
        ->addCancelButton($done_uri, pht('Okay'));

      $message_body = PhorgeAuthMessage::loadMessageText(
        $viewer,
        PhorgeAuthChangeUsernameMessageType::MESSAGEKEY);
      if (strlen($message_body)) {
        $dialog->appendRemarkup($message_body);
      }

      return $dialog;
    }

    $validation_exception = null;
    $username = $user->getUsername();
    if ($request->isFormOrHisecPost()) {
      $username = $request->getStr('username');
      $xactions = array();

      $xactions[] = id(new PhorgeUserTransaction())
        ->setTransactionType(
          PhorgeUserUsernameTransaction::TRANSACTIONTYPE)
        ->setNewValue($username);

      $editor = id(new PhorgeUserTransactionEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setCancelURI($done_uri)
        ->setContinueOnMissingFields(true);

      try {
        $editor->applyTransactions($user, $xactions);
        return id(new AphrontRedirectResponse())->setURI($done_uri);
      } catch (PhorgeApplicationTransactionValidationException $ex) {
        $validation_exception = $ex;
      }

    }

    $instructions = array();

    $instructions[] = pht(
      'If you rename this user, the old username will no longer be tied '.
      'to the user account. Anything which uses the old username in raw '.
      'text (like old commit messages) may no longer associate correctly.');

    $instructions[] = pht(
      'It is generally safe to rename users, but changing usernames may '.
      'create occasional minor complications or confusion with text that '.
      'contains the old username.');

    $instructions[] = pht(
      'The user will receive an email notifying them that you changed their '.
      'username.');

    $instructions[] = null;

    $form = id(new AphrontFormView())
      ->appendChild(
        id(new AphrontFormStaticControl())
          ->setLabel(pht('Old Username'))
          ->setValue($user->getUsername()))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('New Username'))
          ->setValue($username)
          ->setName('username'));

    $dialog = $this->newDialog()
      ->setTitle(pht('Change Username'))
      ->setValidationException($validation_exception);

    foreach ($instructions as $instruction) {
      $dialog->appendParagraph($instruction);
    }

    $dialog
      ->appendForm($form)
      ->addSubmitButton(pht('Rename User'))
      ->addCancelButton($done_uri);

    return $dialog;
  }

}
