<?php

final class PhortunePaymentProviderConfigEditor
  extends PhorgeApplicationTransactionEditor {

  public function getEditorApplicationClass() {
    return 'PhorgePhortuneApplication';
  }

  public function getEditorObjectsDescription() {
    return pht('Phortune Payment Providers');
  }

  public function getTransactionTypes() {
    $types = parent::getTransactionTypes();

    $types[] = PhortunePaymentProviderConfigTransaction::TYPE_CREATE;
    $types[] = PhortunePaymentProviderConfigTransaction::TYPE_PROPERTY;
    $types[] = PhortunePaymentProviderConfigTransaction::TYPE_ENABLE;

    return $types;
  }

  protected function getCustomTransactionOldValue(
    PhorgeLiskDAO $object,
    PhorgeApplicationTransaction $xaction) {
    switch ($xaction->getTransactionType()) {
      case PhortunePaymentProviderConfigTransaction::TYPE_CREATE:
        return null;
      case PhortunePaymentProviderConfigTransaction::TYPE_ENABLE:
        return (int)$object->getIsEnabled();
      case PhortunePaymentProviderConfigTransaction::TYPE_PROPERTY:
        $property_key = $xaction->getMetadataValue(
          PhortunePaymentProviderConfigTransaction::PROPERTY_KEY);
        return $object->getMetadataValue($property_key);
    }

    return parent::getCustomTransactionOldValue($object, $xaction);
  }

  protected function getCustomTransactionNewValue(
    PhorgeLiskDAO $object,
    PhorgeApplicationTransaction $xaction) {

    switch ($xaction->getTransactionType()) {
      case PhortunePaymentProviderConfigTransaction::TYPE_CREATE:
      case PhortunePaymentProviderConfigTransaction::TYPE_PROPERTY:
        return $xaction->getNewValue();
      case PhortunePaymentProviderConfigTransaction::TYPE_ENABLE:
        return (int)$xaction->getNewValue();
    }

    return parent::getCustomTransactionNewValue($object, $xaction);
  }

  protected function applyCustomInternalTransaction(
    PhorgeLiskDAO $object,
    PhorgeApplicationTransaction $xaction) {

    switch ($xaction->getTransactionType()) {
      case PhortunePaymentProviderConfigTransaction::TYPE_CREATE:
        return;
      case PhortunePaymentProviderConfigTransaction::TYPE_PROPERTY:
        $property_key = $xaction->getMetadataValue(
          PhortunePaymentProviderConfigTransaction::PROPERTY_KEY);
        $object->setMetadataValue($property_key, $xaction->getNewValue());
        return;
      case PhortunePaymentProviderConfigTransaction::TYPE_ENABLE:
        return $object->setIsEnabled((int)$xaction->getNewValue());
    }

    return parent::applyCustomInternalTransaction($object, $xaction);
  }

  protected function applyCustomExternalTransaction(
    PhorgeLiskDAO $object,
    PhorgeApplicationTransaction $xaction) {

    switch ($xaction->getTransactionType()) {
      case PhortunePaymentProviderConfigTransaction::TYPE_CREATE:
      case PhortunePaymentProviderConfigTransaction::TYPE_PROPERTY:
      case PhortunePaymentProviderConfigTransaction::TYPE_ENABLE:
        return;
    }

    return parent::applyCustomExternalTransaction($object, $xaction);
  }

}
