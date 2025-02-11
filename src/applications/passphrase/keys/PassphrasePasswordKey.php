<?php

final class PassphrasePasswordKey extends PassphraseAbstractKey {

  public static function loadFromPHID($phid, PhorgeUser $viewer) {
    $key = new PassphrasePasswordKey();
    return $key->loadAndValidateFromPHID(
      $phid,
      $viewer,
      PassphrasePasswordCredentialType::PROVIDES_TYPE);
  }

  public function getPasswordEnvelope() {
    return $this->requireCredential()->getSecret();
  }

}
