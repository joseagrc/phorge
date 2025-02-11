<?php

final class PhorgeEnumConfigType
  extends PhorgeTextConfigType {

  const TYPEKEY = 'enum';

  public function validateStoredValue(
    PhorgeConfigOption $option,
    $value) {

    if (!is_string($value)) {
      throw $this->newException(
        pht(
          'Option "%s" is of type "%s", but the configured value is not '.
          'a string.',
          $option->getKey(),
          $this->getTypeKey()));
    }

    $map = $option->getEnumOptions();
    if (!isset($map[$value])) {
      throw $this->newException(
        pht(
          'Option "%s" is of type "%s", but the current value ("%s") is not '.
          'among the set of valid values: %s.',
          $option->getKey(),
          $this->getTypeKey(),
          $value,
          implode(', ', array_keys($map))));
    }
  }

  protected function newControl(PhorgeConfigOption $option) {
    $map = array(
      '' => pht('(Use Default)'),
    ) + $option->getEnumOptions();

    return id(new AphrontFormSelectControl())
      ->setOptions($map);
  }

}
