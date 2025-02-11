<?php

final class PhorgeEditEngineOrderTransaction
  extends PhorgeEditEngineTransactionType {

  const TRANSACTIONTYPE = 'editengine.config.order';

  public function generateOldValue($object) {
    return $object->getFieldOrder();
  }

  public function applyInternalEffects($object, $value) {
    $object->setFieldOrder($value);
  }

  public function getTitle() {
    return pht(
      '%s reordered the fields in this form.',
      $this->renderAuthor());
  }

  public function hasChangeDetailView() {
    return true;
  }

  public function newChangeDetailView() {
    $viewer = $this->getViewer();

    return id(new PhorgeApplicationTransactionJSONDiffDetailView())
      ->setViewer($viewer)
      ->setOld($this->getOldValue())
      ->setNew($this->getNewValue());
  }

}
