<?php

final class PhorgeBadgesBadgeDescriptionTransaction
  extends PhorgeBadgesBadgeTransactionType {

  const TRANSACTIONTYPE = 'badge.description';

  public function generateOldValue($object) {
    return $object->getDescription();
  }

  public function applyInternalEffects($object, $value) {
    $object->setDescription($value);
  }

  public function getTitle() {
    return pht(
      '%s updated the badge description.',
      $this->renderAuthor());
  }

  public function getTitleForFeed() {
    return pht(
      '%s updated the badge description for %s.',
      $this->renderAuthor(),
      $this->renderObject());
  }

  public function hasChangeDetailView() {
    return true;
  }

  public function getMailDiffSectionHeader() {
    return pht('CHANGES TO BADGE DESCRIPTION');
  }

  public function newChangeDetailView() {
    $viewer = $this->getViewer();

    return id(new PhorgeApplicationTransactionTextDiffDetailView())
      ->setViewer($viewer)
      ->setOldText($this->getOldValue())
      ->setNewText($this->getNewValue());
  }

  public function newRemarkupChanges() {
    $changes = array();

    $changes[] = $this->newRemarkupChange()
      ->setOldValue($this->getOldValue())
      ->setNewValue($this->getNewValue());

    return $changes;
  }


}
