<?php

final class PhorgeCustomFieldEditType
  extends PhorgeEditType {

  private $customField;

  public function setCustomField(PhorgeCustomField $custom_field) {
    $this->customField = $custom_field;
    return $this;
  }

  public function getCustomField() {
    return $this->customField;
  }

  public function getMetadata() {
    $field = $this->getCustomField();
    return parent::getMetadata() + $field->getApplicationTransactionMetadata();
  }

  public function generateTransactions(
    PhorgeApplicationTransaction $template,
    array $spec) {

    $value = idx($spec, 'value');

    $xaction = $this->newTransaction($template)
      ->setNewValue($value);

    $custom_type = PhorgeTransactions::TYPE_CUSTOMFIELD;
    if ($xaction->getTransactionType() == $custom_type) {
      $field = $this->getCustomField();

      $xaction
        ->setOldValue($field->getOldValueForApplicationTransactions())
        ->setMetadataValue('customfield:key', $field->getFieldKey());
    }

    return array($xaction);
  }

  protected function getTransactionValueFromValue($value) {
    $field = $this->getCustomField();

    // Avoid changing the value of the field itself, since later calls would
    // incorrectly reflect the new value.
    $clone = clone $field;
    $clone->setValueFromApplicationTransactions($value);
    return $clone->getNewValueForApplicationTransactions();
  }

}
