<?php

final class PhorgeAuthSSHRevoker
  extends PhorgeAuthRevoker {

  const REVOKERKEY = 'ssh';

  public function getRevokerName() {
    return pht('SSH Keys');
  }

  public function getRevokerDescription() {
    return pht(
      "Revokes all SSH public keys.\n\n".
      "SSH public keys are revoked, not just removed. Users will need to ".
      "generate and upload new, unique keys before they can access ".
      "repositories or other services over SSH.");
  }

  public function revokeAllCredentials() {
    $query = new PhorgeAuthSSHKeyQuery();
    return $this->revokeWithQuery($query);
  }

  public function revokeCredentialsFrom($object) {
    $query = id(new PhorgeAuthSSHKeyQuery())
      ->withObjectPHIDs(array($object->getPHID()));

    return $this->revokeWithQuery($query);
  }

  private function revokeWithQuery(PhorgeAuthSSHKeyQuery $query) {
    $viewer = $this->getViewer();

    // We're only going to revoke keys which have not already been revoked.

    $ssh_keys = $query
      ->setViewer($viewer)
      ->withIsActive(true)
      ->execute();

    $content_source = PhorgeContentSource::newForSource(
      PhorgeDaemonContentSource::SOURCECONST);

    $auth_phid = id(new PhorgeAuthApplication())->getPHID();
    foreach ($ssh_keys as $ssh_key) {
      $xactions = array();
      $xactions[] = $ssh_key->getApplicationTransactionTemplate()
        ->setTransactionType(PhorgeAuthSSHKeyTransaction::TYPE_DEACTIVATE)
        ->setNewValue(1);

      $editor = $ssh_key->getApplicationTransactionEditor()
        ->setActor($viewer)
        ->setActingAsPHID($auth_phid)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->setContentSource($content_source)
        ->setIsAdministrativeEdit(true)
        ->applyTransactions($ssh_key, $xactions);
    }

    return count($ssh_keys);
  }

}
