<?php

final class PhorgePDFCatalogObject
  extends PhorgePDFObject {

  private $pagesObject;

  public function setPagesObject(PhorgePDFPagesObject $pages_object) {
    $this->pagesObject = $this->newChildObject($pages_object);
    return $this;
  }

  public function getPagesObject() {
    return $this->pagesObject;
  }

  protected function writeObject() {
    $this->writeLine('/Type /Catalog');

    $pages_object = $this->getPagesObject();
    if ($pages_object) {
      $this->writeLine('/Pages %d 0 R', $pages_object->getObjectIndex());
    }
  }

}
