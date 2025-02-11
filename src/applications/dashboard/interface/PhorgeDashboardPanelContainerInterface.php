<?php

interface PhorgeDashboardPanelContainerInterface {

  /**
   * Return a list of Dashboard Panel PHIDs used by this container.
   *
   * @return list<phid>
   */
  public function getDashboardPanelContainerPanelPHIDs();

}
