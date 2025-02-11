<?php

final class PhorgeAuthInviteWorker
  extends PhorgeWorker {

  protected function doWork() {
    $data = $this->getTaskData();
    $viewer = PhorgeUser::getOmnipotentUser();

    $address = idx($data, 'address');
    $author_phid = idx($data, 'authorPHID');

    $author = id(new PhorgePeopleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($author_phid))
      ->executeOne();
    if (!$author) {
      throw new PhorgeWorkerPermanentFailureException(
        pht('Invite has invalid author PHID ("%s").', $author_phid));
    }

    $invite = id(new PhorgeAuthInviteQuery())
      ->setViewer($viewer)
      ->withEmailAddresses(array($address))
      ->executeOne();
    if ($invite) {
      // If we're inviting a user who has already been invited, we just
      // regenerate their invite code.
      $invite->regenerateVerificationCode();
    } else {
      // Otherwise, we're creating a new invite.
      $invite = id(new PhorgeAuthInvite())
        ->setEmailAddress($address);
    }

    // Whether this is a new invite or not, tag this most recent author as
    // the invite author.
    $invite->setAuthorPHID($author_phid);

    $code = $invite->getVerificationCode();
    $invite_uri = '/auth/invite/'.$code.'/';
    $invite_uri = PhorgeEnv::getProductionURI($invite_uri);

    $template = idx($data, 'template');
    $template = str_replace('{$INVITE_URI}', $invite_uri, $template);

    $invite->save();

    $mail = id(new PhorgeMetaMTAMail())
      ->addRawTos(array($invite->getEmailAddress()))
      ->setForceDelivery(true)
      ->setSubject(
        pht(
          '[%s] %s has invited you to join %s',
          PlatformSymbols::getPlatformServerName(),
          $author->getFullName(),
          PlatformSymbols::getPlatformServerName()))
      ->setBody($template)
      ->saveAndSend();
  }

}
