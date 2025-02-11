<?php

/**
 * Editor class for creating and adjusting users. This class guarantees data
 * integrity and writes logs when user information changes.
 *
 * @task config     Configuration
 * @task edit       Creating and Editing Users
 * @task role       Editing Roles
 * @task email      Adding, Removing and Changing Email
 * @task internal   Internals
 */
final class PhorgeUserEditor extends PhorgeEditor {

  private $logs = array();


/* -(  Creating and Editing Users  )----------------------------------------- */


  /**
   * @task edit
   */
  public function createNewUser(
    PhorgeUser $user,
    PhorgeUserEmail $email,
    $allow_reassign = false) {

    if ($user->getID()) {
      throw new Exception(pht('User has already been created!'));
    }

    $is_reassign = false;
    if ($email->getID()) {
      if ($allow_reassign) {
        if ($email->getIsPrimary()) {
          throw new Exception(
            pht('Primary email addresses can not be reassigned.'));
        }
        $is_reassign = true;
      } else {
        throw new Exception(pht('Email has already been created!'));
      }
    }

    if (!PhorgeUser::validateUsername($user->getUsername())) {
      $valid = PhorgeUser::describeValidUsername();
      throw new Exception(pht('Username is invalid! %s', $valid));
    }

    // Always set a new user's email address to primary.
    $email->setIsPrimary(1);

    // If the primary address is already verified, also set the verified flag
    // on the user themselves.
    if ($email->getIsVerified()) {
      $user->setIsEmailVerified(1);
    }

    $this->willAddEmail($email);

    $user->openTransaction();
      try {
        $user->save();
        $email->setUserPHID($user->getPHID());
        $email->save();
      } catch (AphrontDuplicateKeyQueryException $ex) {
        // We might have written the user but failed to write the email; if
        // so, erase the IDs we attached.
        $user->setID(null);
        $user->setPHID(null);

        $user->killTransaction();
        throw $ex;
      }

      if ($is_reassign) {
        $log = PhorgeUserLog::initializeNewLog(
          $this->requireActor(),
          $user->getPHID(),
          PhorgeReassignEmailUserLogType::LOGTYPE);
        $log->setNewValue($email->getAddress());
        $log->save();
      }

    $user->saveTransaction();

    if ($email->getIsVerified()) {
      $this->didVerifyEmail($user, $email);
    }

    id(new DiffusionRepositoryIdentityEngine())
      ->didUpdateEmailAddress($email->getAddress());

    return $this;
  }


/* -(  Editing Roles  )------------------------------------------------------ */

  /**
   * @task role
   */
  public function makeSystemAgentUser(PhorgeUser $user, $system_agent) {
    $actor = $this->requireActor();

    if (!$user->getID()) {
      throw new Exception(pht('User has not been created yet!'));
    }

    $user->openTransaction();
      $user->beginWriteLocking();

        $user->reload();
        if ($user->getIsSystemAgent() == $system_agent) {
          $user->endWriteLocking();
          $user->killTransaction();
          return $this;
        }

        $user->setIsSystemAgent((int)$system_agent);
        $user->save();

      $user->endWriteLocking();
    $user->saveTransaction();

    return $this;
  }

  /**
   * @task role
   */
  public function makeMailingListUser(PhorgeUser $user, $mailing_list) {
    $actor = $this->requireActor();

    if (!$user->getID()) {
      throw new Exception(pht('User has not been created yet!'));
    }

    $user->openTransaction();
      $user->beginWriteLocking();

        $user->reload();
        if ($user->getIsMailingList() == $mailing_list) {
          $user->endWriteLocking();
          $user->killTransaction();
          return $this;
        }

        $user->setIsMailingList((int)$mailing_list);
        $user->save();

      $user->endWriteLocking();
    $user->saveTransaction();

    return $this;
  }

/* -(  Adding, Removing and Changing Email  )-------------------------------- */


  /**
   * @task email
   */
  public function addEmail(
    PhorgeUser $user,
    PhorgeUserEmail $email) {

    $actor = $this->requireActor();

    if (!$user->getID()) {
      throw new Exception(pht('User has not been created yet!'));
    }
    if ($email->getID()) {
      throw new Exception(pht('Email has already been created!'));
    }

    // Use changePrimaryEmail() to change primary email.
    $email->setIsPrimary(0);
    $email->setUserPHID($user->getPHID());

    $this->willAddEmail($email);

    $user->openTransaction();
      $user->beginWriteLocking();

        $user->reload();

        try {
          $email->save();
        } catch (AphrontDuplicateKeyQueryException $ex) {
          $user->endWriteLocking();
          $user->killTransaction();

          throw $ex;
        }

        $log = PhorgeUserLog::initializeNewLog(
          $actor,
          $user->getPHID(),
          PhorgeAddEmailUserLogType::LOGTYPE);
        $log->setNewValue($email->getAddress());
        $log->save();

      $user->endWriteLocking();
    $user->saveTransaction();

    id(new DiffusionRepositoryIdentityEngine())
      ->didUpdateEmailAddress($email->getAddress());

    return $this;
  }


  /**
   * @task email
   */
  public function removeEmail(
    PhorgeUser $user,
    PhorgeUserEmail $email) {

    $actor = $this->requireActor();

    if (!$user->getID()) {
      throw new Exception(pht('User has not been created yet!'));
    }
    if (!$email->getID()) {
      throw new Exception(pht('Email has not been created yet!'));
    }

    $user->openTransaction();
      $user->beginWriteLocking();

        $user->reload();
        $email->reload();

        if ($email->getIsPrimary()) {
          throw new Exception(pht("Can't remove primary email!"));
        }
        if ($email->getUserPHID() != $user->getPHID()) {
          throw new Exception(pht('Email not owned by user!'));
        }

        $destruction_engine = id(new PhorgeDestructionEngine())
          ->setWaitToFinalizeDestruction(true)
          ->destroyObject($email);

        $log = PhorgeUserLog::initializeNewLog(
          $actor,
          $user->getPHID(),
          PhorgeRemoveEmailUserLogType::LOGTYPE);
        $log->setOldValue($email->getAddress());
        $log->save();

      $user->endWriteLocking();
    $user->saveTransaction();

    $this->revokePasswordResetLinks($user);
    $destruction_engine->finalizeDestruction();

    return $this;
  }


  /**
   * @task email
   */
  public function changePrimaryEmail(
    PhorgeUser $user,
    PhorgeUserEmail $email) {
    $actor = $this->requireActor();

    if (!$user->getID()) {
      throw new Exception(pht('User has not been created yet!'));
    }
    if (!$email->getID()) {
      throw new Exception(pht('Email has not been created yet!'));
    }

    $user->openTransaction();
      $user->beginWriteLocking();

        $user->reload();
        $email->reload();

        if ($email->getUserPHID() != $user->getPHID()) {
          throw new Exception(pht('User does not own email!'));
        }

        if ($email->getIsPrimary()) {
          throw new Exception(pht('Email is already primary!'));
        }

        if (!$email->getIsVerified()) {
          throw new Exception(pht('Email is not verified!'));
        }

        $old_primary = $user->loadPrimaryEmail();
        if ($old_primary) {
          $old_primary->setIsPrimary(0);
          $old_primary->save();
        }

        $email->setIsPrimary(1);
        $email->save();

        // If the user doesn't have the verified flag set on their account
        // yet, set it. We've made sure the email is verified above. See
        // T12635 for discussion.
        if (!$user->getIsEmailVerified()) {
          $user->setIsEmailVerified(1);
          $user->save();
        }

        $log = PhorgeUserLog::initializeNewLog(
          $actor,
          $user->getPHID(),
          PhorgePrimaryEmailUserLogType::LOGTYPE);
        $log->setOldValue($old_primary ? $old_primary->getAddress() : null);
        $log->setNewValue($email->getAddress());

        $log->save();

      $user->endWriteLocking();
    $user->saveTransaction();

    if ($old_primary) {
      $old_primary->sendOldPrimaryEmail($user, $email);
    }
    $email->sendNewPrimaryEmail($user);

    $this->revokePasswordResetLinks($user);

    return $this;
  }


  /**
   * Verify a user's email address.
   *
   * This verifies an individual email address. If the address is the user's
   * primary address and their account was not previously verified, their
   * account is marked as email verified.
   *
   * @task email
   */
  public function verifyEmail(
    PhorgeUser $user,
    PhorgeUserEmail $email) {
    $actor = $this->requireActor();

    if (!$user->getID()) {
      throw new Exception(pht('User has not been created yet!'));
    }
    if (!$email->getID()) {
      throw new Exception(pht('Email has not been created yet!'));
    }

    $user->openTransaction();
      $user->beginWriteLocking();

        $user->reload();
        $email->reload();

        if ($email->getUserPHID() != $user->getPHID()) {
          throw new Exception(pht('User does not own email!'));
        }

        if (!$email->getIsVerified()) {
          $email->setIsVerified(1);
          $email->save();

          $log = PhorgeUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhorgeVerifyEmailUserLogType::LOGTYPE);
          $log->setNewValue($email->getAddress());
          $log->save();
        }

        if (!$user->getIsEmailVerified()) {
          // If the user just verified their primary email address, mark their
          // account as email verified.
          $user_primary = $user->loadPrimaryEmail();
          if ($user_primary->getID() == $email->getID()) {
            $user->setIsEmailVerified(1);
            $user->save();
          }
        }

      $user->endWriteLocking();
    $user->saveTransaction();

    $this->didVerifyEmail($user, $email);
  }


  /**
   * Reassign an unverified email address.
   */
  public function reassignEmail(
    PhorgeUser $user,
    PhorgeUserEmail $email) {
    $actor = $this->requireActor();

    if (!$user->getID()) {
      throw new Exception(pht('User has not been created yet!'));
    }

    if (!$email->getID()) {
      throw new Exception(pht('Email has not been created yet!'));
    }

    $user->openTransaction();
      $user->beginWriteLocking();

        $user->reload();
        $email->reload();

        $old_user = $email->getUserPHID();

        if ($old_user != $user->getPHID()) {
          if ($email->getIsVerified()) {
            throw new Exception(
              pht('Verified email addresses can not be reassigned.'));
          }
          if ($email->getIsPrimary()) {
            throw new Exception(
              pht('Primary email addresses can not be reassigned.'));
          }

          $email->setUserPHID($user->getPHID());
          $email->save();

          $log = PhorgeUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhorgeReassignEmailUserLogType::LOGTYPE);
          $log->setNewValue($email->getAddress());
          $log->save();
        }

      $user->endWriteLocking();
    $user->saveTransaction();

    id(new DiffusionRepositoryIdentityEngine())
      ->didUpdateEmailAddress($email->getAddress());
  }


/* -(  Internals  )---------------------------------------------------------- */


  /**
   * @task internal
   */
  private function willAddEmail(PhorgeUserEmail $email) {

    // Hard check before write to prevent creation of disallowed email
    // addresses. Normally, the application does checks and raises more
    // user friendly errors for us, but we omit the courtesy checks on some
    // pathways like administrative scripts for simplicity.

    if (!PhorgeUserEmail::isValidAddress($email->getAddress())) {
      throw new Exception(PhorgeUserEmail::describeValidAddresses());
    }

    if (!PhorgeUserEmail::isAllowedAddress($email->getAddress())) {
      throw new Exception(PhorgeUserEmail::describeAllowedAddresses());
    }

    $application_email = id(new PhorgeMetaMTAApplicationEmailQuery())
      ->setViewer(PhorgeUser::getOmnipotentUser())
      ->withAddresses(array($email->getAddress()))
      ->executeOne();
    if ($application_email) {
      throw new Exception($application_email->getInUseMessage());
    }
  }

  public function revokePasswordResetLinks(PhorgeUser $user) {
    // Revoke any outstanding password reset links. If an attacker compromises
    // an account, changes the email address, and sends themselves a password
    // reset link, it could otherwise remain live for a short period of time
    // and allow them to compromise the account again later.

    PhorgeAuthTemporaryToken::revokeTokens(
      $user,
      array($user->getPHID()),
      array(
        PhorgeAuthOneTimeLoginTemporaryTokenType::TOKENTYPE,
        PhorgeAuthPasswordResetTemporaryTokenType::TOKENTYPE,
      ));
  }

  private function didVerifyEmail(
    PhorgeUser $user,
    PhorgeUserEmail $email) {

    $event_type = PhorgeEventType::TYPE_AUTH_DIDVERIFYEMAIL;
    $event_data = array(
      'user' => $user,
      'email' => $email,
    );

    $event = id(new PhorgeEvent($event_type, $event_data))
      ->setUser($user);
    PhutilEventEngine::dispatchEvent($event);
  }


}
