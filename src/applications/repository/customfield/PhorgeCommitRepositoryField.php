<?php

final class PhorgeCommitRepositoryField
  extends PhorgeCommitCustomField {

  public function getFieldKey() {
    return 'diffusion:repository';
  }

  public function getFieldName() {
    return pht('Repository');
  }

  public function getFieldDescription() {
    return pht('Shows repository in email.');
  }

  public function shouldDisableByDefault() {
    return true;
  }

  public function shouldAppearInTransactionMail() {
    return true;
  }

  public function updateTransactionMailBody(
    PhorgeMetaMTAMailBody $body,
    PhorgeApplicationTransactionEditor $editor,
    array $xactions) {

    $repository = $this->getObject()->getRepository();

    $body->addTextSection(
      pht('REPOSITORY'),
      $repository->getMonogram().' '.$repository->getName());
  }

}
