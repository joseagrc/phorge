<?php

final class PhorgeStandardCustomFieldHeader
  extends PhorgeStandardCustomField {

  public function getFieldType() {
    return 'header';
  }

  public function renderEditControl(array $handles) {
    $header = phutil_tag(
      'div',
      array(
        'class' => 'phorge-standard-custom-field-header',
      ),
      $this->getFieldName());
    return id(new AphrontFormStaticControl())
      ->setValue($header);
  }

  public function shouldUseStorage() {
    return false;
  }

  public function getStyleForPropertyView() {
    return 'header';
  }

  public function renderPropertyViewValue(array $handles) {
    return $this->getFieldName();
  }

  public function shouldAppearInApplicationSearch() {
    return false;
  }

  public function shouldAppearInConduitTransactions() {
    return false;
  }

}
