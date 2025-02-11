<?php

final class PhorgeOwnersPackageStatusTransaction
  extends PhorgeOwnersPackageTransactionType {

  const TRANSACTIONTYPE = 'owners.status';

  public function generateOldValue($object) {
    return $object->getStatus();
  }

  public function applyInternalEffects($object, $value) {
    $object->setStatus($value);
  }

  public function getTitle() {
    $new = $this->getNewValue();
    if ($new == PhorgeOwnersPackage::STATUS_ACTIVE) {
      return pht(
        '%s activated this package.',
        $this->renderAuthor());
    } else if ($new == PhorgeOwnersPackage::STATUS_ARCHIVED) {
      return pht(
        '%s archived this package.',
        $this->renderAuthor());
    }
  }

}
