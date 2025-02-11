<?php

final class PhorgeCustomFieldDataNotAvailableException extends Exception {

  public function __construct(PhorgeCustomField $field) {
    parent::__construct(
      pht(
        "Custom field '%s' (with key '%s', of class '%s') is attempting ".
        "to access data which is not available in this context.",
        $field->getFieldName(),
        $field->getFieldKey(),
        get_class($field)));
  }

}
