<?php

final class PhorgeDashboardColumn
  extends Phobject {

  private $columnKey;
  private $classes = array();
  private $refs = array();

  public function setColumnKey($column_key) {
    $this->columnKey = $column_key;
    return $this;
  }

  public function getColumnKey() {
    return $this->columnKey;
  }

  public function addClass($class) {
    $this->classes[] = $class;
    return $this;
  }

  public function getClasses() {
    return $this->classes;
  }

  public function setPanelRefs(array $refs) {
    assert_instances_of($refs, 'PhorgeDashboardPanelRef');
    $this->refs = $refs;
    return $this;
  }

  public function addPanelRef(PhorgeDashboardPanelRef $ref) {
    $this->refs[] = $ref;
    return $this;
  }

  public function getPanelRefs() {
    return $this->refs;
  }

}
