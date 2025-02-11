<?php

final class PhorgeChartDisplayData
  extends Phobject {

  private $wireData;
  private $range;

  public function setWireData(array $wire_data) {
    $this->wireData = $wire_data;
    return $this;
  }

  public function getWireData() {
    return $this->wireData;
  }

  public function setRange(PhorgeChartInterval $range) {
    $this->range = $range;
    return $this;
  }

  public function getRange() {
    return $this->range;
  }

}
