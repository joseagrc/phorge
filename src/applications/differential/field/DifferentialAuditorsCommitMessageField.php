<?php

final class DifferentialAuditorsCommitMessageField
  extends DifferentialCommitMessageCustomField {

  const FIELDKEY = 'phorge:auditors';

  public function getFieldName() {
    return pht('Auditors');
  }

  public function getFieldAliases() {
    return array(
      'Auditor',
    );
  }

  public function parseFieldValue($value) {
    return $this->parseObjectList(
      $value,
      array(
        PhorgePeopleUserPHIDType::TYPECONST,
        PhorgeProjectProjectPHIDType::TYPECONST,
        PhorgeOwnersPackagePHIDType::TYPECONST,
      ));
  }

  public function getCustomFieldKey() {
    return 'phorge:auditors';
  }

  public function isFieldEditable() {
    return true;
  }

  public function isTemplateField() {
    return false;
  }

  public function readFieldValueFromConduit($value) {
    return $this->readStringListFieldValueFromConduit($value);
  }

  public function renderFieldValue($value) {
    return $this->renderHandleList($value);
  }

  protected function readFieldValueFromCustomFieldStorage($value) {
    return $this->readJSONFieldValueFromCustomFieldStorage($value, array());
  }

}
