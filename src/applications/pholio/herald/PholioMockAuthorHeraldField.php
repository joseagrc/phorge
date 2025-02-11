<?php

final class PholioMockAuthorHeraldField
  extends PholioMockHeraldField {

  const FIELDCONST = 'pholio.mock.author';

  public function getHeraldFieldName() {
    return pht('Author');
  }

  public function getHeraldFieldValue($object) {
    return $object->getAuthorPHID();
  }

  protected function getHeraldFieldStandardType() {
    return self::STANDARD_PHID;
  }

  protected function getDatasource() {
    return new PhorgePeopleDatasource();
  }

}
