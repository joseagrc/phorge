<?php

final class PhorgeOwnersPackageDescriptionTransaction
  extends PhorgeOwnersPackageTransactionType {

  const TRANSACTIONTYPE = 'owners.description';

  public function generateOldValue($object) {
    return $object->getDescription();
  }

  public function applyInternalEffects($object, $value) {
    $object->setDescription($value);
  }

  public function getTitle() {
    return pht(
      '%s updated the description for this package.',
      $this->renderAuthor());
  }

  public function getMailDiffSectionHeader() {
    return pht('CHANGES TO PACKAGE DESCRIPTION');
  }

  public function newChangeDetailView() {
    return id(new PhorgeApplicationTransactionTextDiffDetailView())
      ->setViewer($this->getViewer())
      ->setOldText($this->getOldValue())
      ->setNewText($this->getNewValue());
  }

}
