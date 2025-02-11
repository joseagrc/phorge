<?php

/**
 * @task password   Managing Encryption Passwords
 */
abstract class PassphraseCredentialType extends Phobject {

  abstract public function getCredentialType();
  abstract public function getProvidesType();
  abstract public function getCredentialTypeName();
  abstract public function getCredentialTypeDescription();
  abstract public function getSecretLabel();

  public function newSecretControl() {
    return new AphrontFormTextAreaControl();
  }

  public static function getAllTypes() {
    return id(new PhutilClassMapQuery())
      ->setAncestorClass(__CLASS__)
      ->setUniqueMethod('getCredentialType')
      ->execute();
  }

  public static function getAllCreateableTypes() {
    $types = self::getAllTypes();
    foreach ($types as $key => $type) {
      if (!$type->isCreateable()) {
        unset($types[$key]);
      }
    }

    return $types;
  }

  public static function getAllProvidesTypes() {
    $types = array();
    foreach (self::getAllTypes() as $type) {
      $types[] = $type->getProvidesType();
    }
    return array_unique($types);
  }

  public static function getTypeByConstant($constant) {
    $all = self::getAllTypes();
    $all = mpull($all, null, 'getCredentialType');
    return idx($all, $constant);
  }


  /**
   * Can users create new credentials of this type?
   *
   * @return bool True if new credentials of this type can be created.
   */
  public function isCreateable() {
    return true;
  }


  public function didInitializeNewCredential(
    PhorgeUser $actor,
    PassphraseCredential $credential) {
    return $credential;
  }

  public function hasPublicKey() {
    return false;
  }

  public function getPublicKey(
    PhorgeUser $viewer,
    PassphraseCredential $credential) {
    return null;
  }


/* -(  Passwords  )---------------------------------------------------------- */


  /**
   * Return true to show an additional "Password" field. This is used by
   * SSH credentials to strip passwords off private keys.
   *
   * @return bool True if a password field should be shown to the user.
   *
   * @task password
   */
  public function shouldShowPasswordField() {
    return false;
  }


  /**
   * Return the label for the password field, if one is shown.
   *
   * @return string   Human-readable field label.
   *
   * @task password
   */
  public function getPasswordLabel() {
    return pht('Password');
  }

  public function shouldRequireUsername() {
    return true;
  }

}
